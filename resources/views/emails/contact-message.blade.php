<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ExamGuard contact message</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1e293b; line-height: 1.6;">
    <h2 style="margin: 0 0 12px;">New contact message</h2>
    <p><strong>Name:</strong> {{ $data['name'] }}</p>
    <p><strong>Email:</strong> {{ $data['email'] }}</p>
    <p><strong>Subject:</strong> {{ $data['subject'] }}</p>
    <p><strong>Message:</strong></p>
    <p style="white-space: pre-wrap; background: #f8fafc; padding: 12px; border-radius: 8px;">{{ $data['message'] }}</p>
</body>
</html>
