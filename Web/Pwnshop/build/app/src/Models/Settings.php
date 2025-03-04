<?php
namespace App\Models;

use App\Database\Database;
use PDO;
use PDOException;
use Exception;
use Less_Parser;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use App\Security\FileScanner;


class Settings {
    private $db;
    private $lessDirectory = __DIR__ . '/../../resources/less';
    private $fileScanner;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->fileScanner = new FileScanner($this->lessDirectory);
        if (!is_dir($this->lessDirectory)) {
            mkdir($this->lessDirectory, 0755, true);
        }
    }

    public function updateSettings($data) {
        try {
            $stmt = $this->db->prepare("
                UPDATE settings 
                SET site_name = ?,
                    contact_email = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = 1
            ");
            
            return $stmt->execute([
                $data['siteName'],
                $data['contactEmail']
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getGlobalSettings() {
        try {
            $stmt = $this->db->query("SELECT * FROM settings WHERE id = 1");
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }

    public function updateCustomCss($css) {
        try {
            $customLessPath = __DIR__ . '/../../resources/less/custom.less';
            file_put_contents($customLessPath, $css);
            return $this->generateCSS();
        } catch (Exception $e) {
            return false;
        }
    }

    public function uploadLessFile($file, $path = '') {
        try {
            $scanner = new FileScanner($this->lessDirectory);
                
            $result = $scanner->validateFile($file, 'less');
            if ($result['success'] === false) {
                return $result;
            }
            $targetDir = $this->lessDirectory;
            if ($path) {
                if (!$scanner->validatePath($path)) {
                    return [
                        'success' => false,
                        'message' => 'Destination path not allowed',
                        'status' => 400
                    ];
                }
                $targetDir .= '/' . $scanner->normalizePath($path);
            }
            
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            $fileName = $scanner->generateSafeFileName($file['name']);
            $targetFile = $targetDir . '/' . $fileName;
            if (file_exists($targetFile)) {
                return [
                    'success' => false,
                    'message' => 'The file already exists',
                    'status' => 400
                ];
            }
            if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
                return [
                    'success' => false,
                    'message' => 'Error uploading the file',
                    'status' => 500
                ];
            }


            return [
                'success' => true,
                'message' => 'File uploaded successfully',
                'status' => 200
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'status' => 500
            ];
        }
    }

    public function getLessFiles() {
        try {
            $files = [];
            
            if (!is_dir($this->lessDirectory)) {
                return $files;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->lessDirectory)
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'less') {
                    $relativePath = str_replace($this->lessDirectory . '/', '', $file->getPathname());
                    $files[] = [
                        'path' => $relativePath,
                        'size' => $file->getSize(),
                        'modified' => $file->getMTime()
                    ];
                }
            }

            return $files;
        } catch (Exception $e) {
            return [];
        }
    }

    public function deleteLessFile($path) {
        $fullPath = $this->lessDirectory . '/' . $path;
        
        if (!file_exists($fullPath)) {
            return false;
        }

        if (unlink($fullPath)) {
            return true;
        }

        return false;
    }

    private function getImportDirectories(): array {
        try {
            $stmt = $this->db->query("SELECT physical_path, import_path FROM less_import_directories");
            $directories = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $directories[$row['physical_path']] = $row['import_path'];
            }
            $directories[$this->lessDirectory] = '';
            return $directories;
        } catch (PDOException $e) {
            return [$this->lessDirectory => ''];
        }
    }

    public function addImportDirectory(string $physicalPath, string $importPath): bool {
        try {
            $this->fileScanner->validatePath($physicalPath);
            $this->fileScanner->validatePath($importPath);
            $stmt = $this->db->prepare("
                INSERT INTO less_import_directories (physical_path, import_path)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE import_path = VALUES(import_path)
            ");
            
            return $stmt->execute([
                rtrim($physicalPath, '/'),
                trim($importPath, '/')
            ]);
        } catch (Exception $e) {
            return false;
        }
    }

    public function removeImportDirectory(string $physicalPath): bool {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM less_import_directories 
                WHERE physical_path = ?
            ");
            
            return $stmt->execute([rtrim($physicalPath, '/')]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function listImportDirectories(): array {
        try {
            $stmt = $this->db->query("
                SELECT id, physical_path, import_path, created_at, updated_at 
                FROM less_import_directories
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    private function generateCSS() {
        try {
            $themeCssPath = __DIR__ . '/../../public/assets/css/main.css';
            $css = file_get_contents($themeCssPath);
            
            $customLessPath = __DIR__ . '/../../resources/less/custom.less';
            if (file_exists($customLessPath)) {
                $customLess = file_get_contents($customLessPath);
                $parser = new \Less_Parser();
                $importDirs = $this->getImportDirectories();
                $parser->SetImportDirs($importDirs);
                $parser->parse($customLess);
                $customCSS = $parser->getCss();
                $css .= "\n/* Custom CSS */\n" . $customCSS;
            }
            
            $cssPath = __DIR__ . '/../../public/assets/css/theme.css';
            
            $cssDir = dirname($cssPath);
            if (!is_dir($cssDir)) {
                mkdir($cssDir, 0755, true);
            }
            
            file_put_contents($cssPath, $css);
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
} 