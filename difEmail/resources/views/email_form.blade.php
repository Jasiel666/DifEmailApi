<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Send Email</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded shadow-md w-full max-w-md">
        <h1 class="text-2xl font-bold mb-6">Send Email</h1>

        @if(session('success'))
            <div class="mb-4 p-3 bg-green-100 text-green-700 rounded">
                {{ session('success') }}
            </div>
        @endif
        
        @if(session('error'))
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
                {{ session('error') }}
            </div>
        @endif
        

        @if(session('access_token'))
            <div class="mb-4 p-3 bg-blue-100 text-blue-700 rounded">
                Authenticated as {{ session('email') }}
            </div>
        @else
            <div class="mb-4 p-3 bg-yellow-100 text-yellow-700 rounded">
                You need to authenticate with Google to send emails
            </div>
        @endif

        <form action="{{ route('email.send') }}" method="POST">
            @csrf
            
            <div class="mb-4">
                <label class="block mb-1 font-semibold">Subject:</label>
                <input type="text" name="subject" required 
                       class="w-full border rounded px-3 py-2" 
                       placeholder="Enter Subject"
                       value="{{ old('subject') }}">
            </div>
            
            <div class="mb-4">
                <label class="block mb-1 font-semibold">Body:</label>
                <textarea name="body" required 
                          class="w-full border rounded px-3 py-2 h-32" 
                          placeholder="Write your message...">{{ old('body') }}</textarea>
            </div>
            
            <div class="flex space-x-2">
                @if(!session('access_token'))
                <a href="{{ route('email.auth') }}" 
                   class="w-1/2 bg-gray-600 text-white py-2 rounded hover:bg-gray-700 text-center">
                    Authenticate
                </a>
                @endif
                
                <button type="submit" 
                        class="{{ session('access_token') ? 'w-full' : 'w-1/2' }} bg-blue-600 text-white py-2 rounded hover:bg-blue-700">
                    {{ session('access_token') ? 'Send Email' : 'Send & Authenticate' }}
                </button>
            </div>
        </form>
    </div>
</body>
</html>