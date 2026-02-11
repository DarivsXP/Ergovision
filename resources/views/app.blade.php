<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Ergovision AI') }}</title>

        <meta name="description" content="Improve your posture with real-time AI correction. Ergovision uses your webcam to detect slouching and protect your spinal health. No equipment needed.">
        <meta name="keywords" content="posture, AI, health, ergonomics, webcam, spine health, sitting correction, ergovision">
        <meta name="author" content="Ergovision Team">
        <meta name="theme-color" content="#4f46e5"> <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url('/') }}">
        <meta property="og:title" content="Ergovision AI - Real-Time Posture Correction">
        <meta property="og:description" content="Stop slouching today. Ergovision monitors your posture in real-time and alerts you when to sit up straight.">
        <meta property="og:image" content="{{ asset('social-share.jpg') }}"> 

        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:url" content="{{ url('/') }}">
        <meta name="twitter:title" content="Ergovision AI - Fix Your Posture">
        <meta name="twitter:description" content="Real-time AI posture correction directly in your browser.">
        <meta name="twitter:image" content="{{ asset('social-share.jpg') }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <script src="https://cdn.jsdelivr.net/npm/@mediapipe/pose/pose.js" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils/camera_utils.js" crossorigin="anonymous"></script>

        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-slate-900 text-white">
        @inertia
    </body>
</html>