<?php
return [
    // URL của Flask API (AI service)
    'flask_api_url' => 'http://localhost:5000',
    
    // Model settings
    'embedding_model' => 'keepitreal/vietnamese-sbert',
    
    // Similarity threshold
    'similarity_threshold' => 0.3,
    
    // Timeout cho API calls (giây)
    'api_timeout' => 30,
    
    // Enable/disable chatbot
    'chatbot_enabled' => true,
    
    // Debug mode
    'debug' => false
];
?>