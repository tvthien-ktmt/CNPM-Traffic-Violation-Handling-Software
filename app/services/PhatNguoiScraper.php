<?php
// File: app/services/PhatNguoiScraper.php
// Tạo thư mục services nếu chưa có
namespace App\Services;

class PhatNguoiScraper {
    // Code scraping từ phatnguoi.vn ở đây
    // [Toàn bộ code PhatNguoiScraper class]
    /**
     * Scrape dữ liệu từ phatnguoi.vn (họ đã scrape csgt.vn rồi)
     */
    public function scrapeFromPhatNguoi($licensePlate, $vehicleType = 1) {
        try {
            $ch = curl_init();
            
            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://phatnguoi.vn/',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query([
                    'BienKS' => $licensePlate,
                    'Xe' => $vehicleType,
                    'anhcoder_token' => $this->generateToken()
                ]),
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                CURLOPT_REFERER => 'https://phatnguoi.vn/',
                CURLOPT_TIMEOUT => 15,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_COOKIEJAR => '/tmp/phatnguoi_cookies.txt',
                CURLOPT_COOKIEFILE => '/tmp/phatnguoi_cookies.txt',
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/x-www-form-urlencoded',
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Language: vi,en-US;q=0.9,en;q=0.8',
                    'Cache-Control: no-cache',
                    'Origin: https://phatnguoi.vn'
                ]
            ]);
            
            $html = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                return $this->parsePhatNguoiResponse($html);
            }
            
        } catch (Exception $e) {
            error_log("PhatNguoi Scraping Error: " . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Parse HTML response từ phatnguoi.vn
     */
    private function parsePhatNguoiResponse($html) {
        $violations = [];
        
        // Tìm kết quả trong div#ketquatracuu
        if (preg_match('/<div[^>]*id="ketquatracuu"[^>]*>(.*?)<\/div>/s', $html, $resultDiv)) {
            $content = $resultDiv[1];
            
            // Nếu có thông báo không tìm thấy
            if (strpos($content, 'Không tìm thấy') !== false || 
                strpos($content, 'không có vi phạm') !== false) {
                return [];
            }
            
            // Parse các vi phạm từ bảng
            if (preg_match_all('/<tr[^>]*>(.*?)<\/tr>/s', $content, $rows)) {
                foreach ($rows[1] as $row) {
                    if (preg_match_all('/<td[^>]*>(.*?)<\/td>/s', $row, $cells)) {
                        if (count($cells[1]) >= 4) {
                            $violations[] = [
                                'violation_date' => trim(strip_tags($cells[1][0])),
                                'license_plate' => trim(strip_tags($cells[1][1])),
                                'violation_type' => trim(strip_tags($cells[1][2])),
                                'location' => trim(strip_tags($cells[1][3])),
                                'fine_amount' => $this->extractFineAmount($cells[1][4] ?? ''),
                                'status' => 'pending',
                                'source' => 'phatnguoi.vn'
                            ];
                        }
                    }
                }
            }
            
            // Nếu không có bảng, tìm thông báo lỗi
            if (empty($violations)) {
                if (preg_match('/<div[^>]*class="[^"]*alert[^"]*"[^>]*>(.*?)<\/div>/s', $content, $alert)) {
                    $message = trim(strip_tags($alert[1]));
                    if (!empty($message)) {
                        throw new Exception($message);
                    }
                }
            }
        }
        
        return $violations;
    }
    
    /**
     * Scrape trực tiếp từ CSGT nếu cần
     */
    public function scrapeFromCsgt($licensePlate) {
        try {
            $ch = curl_init();
            
            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://csgt.vn/tra-cuu-phat-nguoi',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query([
                    'BienKS' => $licensePlate,
                    'Xe' => '1'
                ]),
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                CURLOPT_TIMEOUT => 15,
                CURLOPT_FOLLOWLOCATION => true
            ]);
            
            $html = curl_exec($ch);
            curl_close($ch);
            
            return $this->parseCsgtResponse($html);
            
        } catch (Exception $e) {
            error_log("CSGT Scraping Error: " . $e->getMessage());
            return false;
        }
    }
    
    private function parseCsgtResponse($html) {
        // Logic parse CSGT tương tự
        // ...
        return []; // placeholder
    }
    
    private function extractFineAmount($text) {
        preg_match('/([\d,]+)/', $text, $matches);
        return isset($matches[1]) ? (float) str_replace(',', '', $matches[1]) : 0;
    }
    
    private function generateToken() {
        return md5(date('Y-m-d-H') . 'phatnguoi_secret');
    }
}
?>