<?php

class ApiController
{
    public function chatbot()
    {
        if (!isset($_POST["question"])) {
            echo json_encode(["answer" => "Không có dữ liệu câu hỏi."]);
            return;
        }

        $question = $_POST["question"];

        // Gọi FastAPI
        $url = "http://127.0.0.1:8000/chatbot";

        $payload = json_encode(["question" => $question]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        echo $response;
    }
}
