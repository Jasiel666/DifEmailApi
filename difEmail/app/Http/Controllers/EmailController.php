<?php

namespace App\Http\Controllers;

use Google\Client;
use Google\Service\Gmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class EmailController extends Controller
{
    private $client;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setClientId(env('GOOGLE_CLIENT_ID'));
        $this->client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $this->client->setRedirectUri('http://127.0.0.1:8000/email/callback'); 
        $this->client->addScope(Gmail::GMAIL_SEND);
        $this->client->addScope(Gmail::GMAIL_READONLY); 
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
        $this->client->setIncludeGrantedScopes(true);
    }
    public function showEmailForm()
    {
        
        Log::info('Email form accessed, session token status:', [
            'has_token' => Session::has('access_token'),
            'token_structure' => Session::has('access_token') ? 
                array_keys(Session::get('access_token')) : 'No token'
        ]);
        
        return view('email_form');
    }

    public function showAuth()
    {
        return redirect()->away($this->client->createAuthUrl());
    }

    public function sendEmail(Request $request)
    {
        Log::info('Send email requested, checking token');
        
        if (!Session::has('access_token')) {
            Log::info('No access token found in session, redirecting to auth');
            return redirect()->route('email.auth');
        }
    
        try {
            $accessToken = Session::get('access_token');
            Log::info('Token from session:', ['structure' => array_keys($accessToken)]);
            
            // Validate token structure
            if (!is_array($accessToken) || !isset($accessToken['access_token'])) {
                Log::error('Invalid token format in session:', ['token' => $accessToken]);
                Session::forget('access_token');
                return redirect()->route('email.auth')->with('error', 'Invalid authentication token');
            }
            
            $this->client->setAccessToken($accessToken);
    
            if ($this->client->isAccessTokenExpired()) {
                Log::info('Token expired, attempting refresh');
                $refreshToken = $accessToken['refresh_token'] ?? null;
                
                if (!$refreshToken) {
                    Log::error('No refresh token available');
                    Session::forget('access_token');
                    return redirect()->route('email.auth');
                }
                
                try {
                    $newToken = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                    
                    if (!isset($newToken['access_token'])) {
                        throw new \Exception('Invalid refresh response');
                    }
                    
                    if (!isset($newToken['refresh_token']) && isset($accessToken['refresh_token'])) {
                        $newToken['refresh_token'] = $accessToken['refresh_token'];
                    }
                    
                    Session::put('access_token', $newToken);
                    $this->client->setAccessToken($newToken);
                    Log::info('Token refreshed successfully');
                } catch (\Exception $e) {
                    Log::error('Token refresh failed: ' . $e->getMessage());
                    Session::forget('access_token');
                    return redirect()->route('email.auth');
                }
            }

            $gmailService = new Gmail($this->client);

            try {
                $profile = $gmailService->users->getProfile('me');
                $fromEmail = $profile->getEmailAddress();
            } catch (\Exception $e) {
                Log::error('Failed to get profile: '.$e->getMessage());
                return back()->with('error', 'Failed to verify your email address');
            }

            $message = new \Google\Service\Gmail\Message();
            $rawMessage = strtr(base64_encode(
                "From: {$fromEmail}\r\n".
                "To: jaasbazz66@gmail.com\r\n".
                "Subject: {$request->input('subject')}\r\n\r\n".
                $request->input('body')
            ), ['+' => '-', '/' => '_', '=' => '']);

            $message->setRaw($rawMessage);
            $gmailService->users_messages->send('me', $message);
            
            return back()->with('success', 'Email sent successfully!');
            
        } catch (\Exception $e) {
            Log::error('Email send failed: '.$e->getMessage());
            return back()->with('error', 'Error sending email: '.$e->getMessage());
        }
    }

    public function callback(Request $request)
    {
        if (!$request->has('code')) {
            return redirect('/')->with('error', 'No authorization code received');
        }

        try {
            Log::info('Received auth code from Google');
            $token = $this->client->fetchAccessTokenWithAuthCode($request->code);
            Log::info('Token response structure:', ['keys' => array_keys($token)]);
            
            if (!isset($token['access_token'])) {
                throw new \Exception('Invalid token format - missing access_token');
            }

            Session::put('access_token', $token);
            
            $this->client->setAccessToken($token);
            $gmail = new Gmail($this->client); 
            $profile = $gmail->users->getProfile('me');
            Session::put('email', $profile->getEmailAddress());
            
            return redirect()->route('email.form');
            
        } catch (\Exception $e) {
            Log::error('Auth callback failed: '.$e->getMessage());
            return redirect('/')->with('error', 'Authentication failed: '.$e->getMessage());
        }
    }
}