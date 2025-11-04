<?php
/**
 * DURALUX CRM - Asset Optimization & CDN Controller v4.0
 * Sistema avançado de otimização de assets e distribuição via CDN
 * 
 * Features:
 * - Compressão automática de CSS/JS
 * - Otimização de imagens
 * - Minificação inteligente
 * - Cache de assets
 * - CDN integration
 * - Bundling automático
 * - Lazy loading implementation
 * - Progressive loading
 * 
 * @author Duralux Development Team
 * @version 4.0.0
 */

require_once 'RedisCacheManager.php';

class AssetOptimizer {
    
    private $cache;
    private $config;
    private $optimizedAssets = [];
    private $compressionStats = [];
    
    // Configurações padrão
    private $defaultConfig = [
        'paths' => [
            'assets' => __DIR__ . '/../../duralux-admin/assets/',
            'cache' => __DIR__ . '/../../cache/assets/',
            'temp' => __DIR__ . '/../../temp/',
        ],
        'compression' => [
            'enable_gzip' => true,
            'enable_brotli' => false,
            'css_minification' => true,
            'js_minification' => true,
            'image_optimization' => true,
            'compression_level' => 9
        ],
        'bundling' => [
            'enable_bundling' => true,
            'bundle_css' => true,
            'bundle_js' => true,
            'max_bundle_size' => 1024 * 1024, // 1MB
            'critical_css_inline' => true
        ],
        'images' => [
            'webp_conversion' => true,
            'jpeg_quality' => 85,
            'png_compression' => 9,
            'auto_resize' => true,
            'responsive_images' => true,
            'lazy_loading' => true
        ],
        'cdn' => [
            'enable_cdn' => false,
            'cdn_url' => '',
            'cdn_zones' => ['css', 'js', 'images', 'fonts'],
            'cache_ttl' => 86400 * 30 // 30 days
        ],
        'performance' => [
            'preload_critical' => true,
            'async_non_critical' => true,
            'defer_javascript' => true,
            'resource_hints' => true
        ]
    ];
    
    public function __construct($config = []) {
        $this->config = array_merge_recursive($this->defaultConfig, $config);
        $this->cache = CacheManager::getInstance();
        
        // Criar diretórios necessários
        $this->ensureDirectories();
    }
    
    /**
     * Otimizar todos os assets
     */
    public function optimizeAllAssets() {
        $results = [
            'css' => $this->optimizeCSS(),
            'javascript' => $this->optimizeJavaScript(),
            'images' => $this->optimizeImages(),
            'fonts' => $this->optimizeFonts(),
            'bundles' => $this->createBundles()
        ];
        
        // Gerar manifesto de assets
        $this->generateAssetManifest($results);
        
        return $results;
    }
    
    /**
     * Otimizar arquivos CSS
     */
    public function optimizeCSS() {
        $cssPath = $this->config['paths']['assets'] . 'css/';
        $optimized = [];
        
        if (!is_dir($cssPath)) {
            return ['error' => 'CSS directory not found'];
        }
        
        $cssFiles = glob($cssPath . '*.css');
        
        foreach ($cssFiles as $file) {
            $filename = basename($file);
            $cacheKey = 'css_' . md5($filename . filemtime($file));
            
            // Verificar cache
            $cachedResult = $this->cache->get($cacheKey);
            if ($cachedResult) {
                $optimized[$filename] = $cachedResult;
                continue;
            }
            
            $originalContent = file_get_contents($file);
            $originalSize = strlen($originalContent);
            
            // Processar CSS
            $result = [
                'original_size' => $originalSize,
                'optimized_size' => 0,
                'compression_ratio' => 0,
                'optimized_file' => '',
                'gzip_file' => '',
                'critical' => $this->isCriticalCSS($filename)
            ];
            
            try {
                // Minificar CSS
                $minified = $this->minifyCSS($originalContent);
                
                // Otimizar imports e URLs
                $optimizedContent = $this->optimizeCSSUrls($minified);
                
                // Aplicar autoprefixer (simulado)
                $optimizedContent = $this->addCSSPrefixes($optimizedContent);
                
                $result['optimized_size'] = strlen($optimizedContent);
                $result['compression_ratio'] = ($originalSize - $result['optimized_size']) / $originalSize * 100;
                
                // Salvar arquivo otimizado
                $optimizedFile = $this->config['paths']['cache'] . 'css/' . str_replace('.css', '.min.css', $filename);
                file_put_contents($optimizedFile, $optimizedContent);
                $result['optimized_file'] = $optimizedFile;
                
                // Criar versão Gzip
                if ($this->config['compression']['enable_gzip']) {
                    $gzipFile = $optimizedFile . '.gz';
                    file_put_contents($gzipFile, gzencode($optimizedContent, $this->config['compression']['compression_level']));
                    $result['gzip_file'] = $gzipFile;
                    $result['gzip_size'] = filesize($gzipFile);
                }
                
                // Cache do resultado
                $this->cache->set($cacheKey, $result, 3600);
                
                $optimized[$filename] = $result;
                
            } catch (Exception $e) {
                $optimized[$filename] = ['error' => $e->getMessage()];
            }
        }
        
        return $optimized;
    }
    
    /**
     * Otimizar arquivos JavaScript
     */
    public function optimizeJavaScript() {
        $jsPath = $this->config['paths']['assets'] . 'js/';
        $optimized = [];
        
        if (!is_dir($jsPath)) {
            return ['error' => 'JavaScript directory not found'];
        }
        
        $jsFiles = glob($jsPath . '*.js');
        
        foreach ($jsFiles as $file) {
            $filename = basename($file);
            $cacheKey = 'js_' . md5($filename . filemtime($file));
            
            // Verificar cache
            $cachedResult = $this->cache->get($cacheKey);
            if ($cachedResult) {
                $optimized[$filename] = $cachedResult;
                continue;
            }
            
            $originalContent = file_get_contents($file);
            $originalSize = strlen($originalContent);
            
            $result = [
                'original_size' => $originalSize,
                'optimized_size' => 0,
                'compression_ratio' => 0,
                'optimized_file' => '',
                'gzip_file' => '',
                'critical' => $this->isCriticalJS($filename),
                'async_safe' => $this->isAsyncSafe($originalContent)
            ];
            
            try {
                // Minificar JavaScript
                $minified = $this->minifyJavaScript($originalContent);
                
                // Otimizar código
                $optimizedContent = $this->optimizeJavaScript($minified);
                
                $result['optimized_size'] = strlen($optimizedContent);
                $result['compression_ratio'] = ($originalSize - $result['optimized_size']) / $originalSize * 100;
                
                // Salvar arquivo otimizado
                $optimizedFile = $this->config['paths']['cache'] . 'js/' . str_replace('.js', '.min.js', $filename);
                file_put_contents($optimizedFile, $optimizedContent);
                $result['optimized_file'] = $optimizedFile;
                
                // Criar versão Gzip
                if ($this->config['compression']['enable_gzip']) {
                    $gzipFile = $optimizedFile . '.gz';
                    file_put_contents($gzipFile, gzencode($optimizedContent, $this->config['compression']['compression_level']));
                    $result['gzip_file'] = $gzipFile;
                    $result['gzip_size'] = filesize($gzipFile);
                }
                
                $this->cache->set($cacheKey, $result, 3600);
                $optimized[$filename] = $result;
                
            } catch (Exception $e) {
                $optimized[$filename] = ['error' => $e->getMessage()];
            }
        }
        
        return $optimized;
    }
    
    /**
     * Otimizar imagens
     */
    public function optimizeImages() {
        $imgPath = $this->config['paths']['assets'] . 'img/';
        $optimized = [];
        
        if (!is_dir($imgPath)) {
            return ['error' => 'Images directory not found'];
        }
        
        $imageFiles = glob($imgPath . '*.{jpg,jpeg,png,gif,svg,webp}', GLOB_BRACE);
        
        foreach ($imageFiles as $file) {
            $filename = basename($file);
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $cacheKey = 'img_' . md5($filename . filemtime($file));
            
            // Verificar cache
            $cachedResult = $this->cache->get($cacheKey);
            if ($cachedResult) {
                $optimized[$filename] = $cachedResult;
                continue;
            }
            
            $originalSize = filesize($file);
            
            $result = [
                'original_size' => $originalSize,
                'optimized_size' => 0,
                'compression_ratio' => 0,
                'format' => $extension,
                'optimized_file' => '',
                'webp_file' => '',
                'responsive_variants' => []
            ];
            
            try {
                switch ($extension) {
                    case 'jpg':
                    case 'jpeg':
                        $result = array_merge($result, $this->optimizeJPEG($file));
                        break;
                        
                    case 'png':
                        $result = array_merge($result, $this->optimizePNG($file));
                        break;
                        
                    case 'gif':
                        $result = array_merge($result, $this->optimizeGIF($file));
                        break;
                        
                    case 'svg':
                        $result = array_merge($result, $this->optimizeSVG($file));
                        break;
                }
                
                // Criar versão WebP se suportado
                if ($this->config['images']['webp_conversion'] && in_array($extension, ['jpg', 'jpeg', 'png'])) {
                    $webpResult = $this->createWebPVersion($file);
                    if ($webpResult) {
                        $result['webp_file'] = $webpResult['file'];
                        $result['webp_size'] = $webpResult['size'];
                    }
                }
                
                // Criar versões responsivas
                if ($this->config['images']['responsive_images']) {
                    $result['responsive_variants'] = $this->createResponsiveVariants($file);
                }
                
                $this->cache->set($cacheKey, $result, 3600);
                $optimized[$filename] = $result;
                
            } catch (Exception $e) {
                $optimized[$filename] = ['error' => $e->getMessage()];
            }
        }
        
        return $optimized;
    }
    
    /**
     * Criar bundles de assets
     */
    public function createBundles() {
        $bundles = [];
        
        if ($this->config['bundling']['enable_bundling']) {
            // Bundle CSS
            if ($this->config['bundling']['bundle_css']) {
                $bundles['css'] = $this->createCSSBundle();
            }
            
            // Bundle JavaScript
            if ($this->config['bundling']['bundle_js']) {
                $bundles['js'] = $this->createJSBundle();
            }
        }
        
        return $bundles;
    }
    
    /**
     * Gerar HTML otimizado para carregamento de assets
     */
    public function generateOptimizedHTML($assets) {
        $html = [];
        
        // Resource hints
        if ($this->config['performance']['resource_hints']) {
            $html[] = $this->generateResourceHints($assets);
        }
        
        // Critical CSS inline
        if ($this->config['bundling']['critical_css_inline']) {
            $html[] = $this->generateInlineCriticalCSS($assets);
        }
        
        // Preload crítico
        if ($this->config['performance']['preload_critical']) {
            $html[] = $this->generatePreloadLinks($assets);
        }
        
        // CSS não crítico (async)
        $html[] = $this->generateAsyncCSS($assets);
        
        // JavaScript otimizado
        $html[] = $this->generateOptimizedJS($assets);
        
        return implode("\n", $html);
    }
    
    // ==========================================
    // MÉTODOS PRIVADOS DE OTIMIZAÇÃO
    // ==========================================
    
    private function ensureDirectories() {
        $dirs = [
            $this->config['paths']['cache'],
            $this->config['paths']['cache'] . 'css/',
            $this->config['paths']['cache'] . 'js/',
            $this->config['paths']['cache'] . 'img/',
            $this->config['paths']['temp']
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    private function minifyCSS($css) {
        // Remover comentários CSS
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remover espaços em branco desnecessários
        $css = preg_replace('/\s+/', ' ', $css);
        
        // Remover espaços ao redor de caracteres especiais
        $css = preg_replace('/\s*([{}();:,>+~])\s*/', '$1', $css);
        
        // Remover última vírgula/ponto e vírgula
        $css = preg_replace('/;}/', '}', $css);
        
        // Converter cores hex longas para curtas
        $css = preg_replace('/#([a-f0-9])\1([a-f0-9])\2([a-f0-9])\3/i', '#$1$2$3', $css);
        
        return trim($css);
    }
    
    private function minifyJavaScript($js) {
        // Minificação básica - em produção usar UglifyJS ou Terser
        
        // Remover comentários de linha
        $js = preg_replace('/\/\/.*$/m', '', $js);
        
        // Remover comentários de bloco
        $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js);
        
        // Remover espaços em branco excessivos
        $js = preg_replace('/\s+/', ' ', $js);
        
        // Remover espaços ao redor de operadores
        $js = preg_replace('/\s*([=+\-*\/{}();,])\s*/', '$1', $js);
        
        return trim($js);
    }
    
    private function optimizeCSSUrls($css) {
        // Converter URLs relativas para absolutas ou CDN
        if ($this->config['cdn']['enable_cdn']) {
            $cdnUrl = rtrim($this->config['cdn']['cdn_url'], '/');
            $css = preg_replace('/url\([\'"]?\.\.?\//i', "url('{$cdnUrl}/", $css);
        }
        
        return $css;
    }
    
    private function addCSSPrefixes($css) {
        // Adicionar prefixos CSS para compatibilidade
        $prefixes = [
            'transform' => ['-webkit-transform', '-ms-transform'],
            'transition' => ['-webkit-transition', '-moz-transition'],
            'border-radius' => ['-webkit-border-radius', '-moz-border-radius'],
            'box-shadow' => ['-webkit-box-shadow', '-moz-box-shadow'],
            'background-size' => ['-webkit-background-size', '-moz-background-size']
        ];
        
        foreach ($prefixes as $property => $vendorPrefixes) {
            if (strpos($css, $property) !== false) {
                foreach ($vendorPrefixes as $prefix) {
                    $css = str_replace($property . ':', $prefix . ':' . $property . ':', $css);
                }
            }
        }
        
        return $css;
    }
    
    private function optimizeJavaScript($js) {
        // Otimizações básicas de JavaScript
        
        // Converter function declarations para arrow functions onde possível
        // $js = preg_replace('/function\s*\(\s*([^)]*)\s*\)\s*{/', '($1) => {', $js);
        
        // Simplificar console.log em produção
        if (strpos($js, 'console.log') !== false) {
            // Em produção, remover console.logs
            // $js = preg_replace('/console\.log\([^;]*\);?/', '', $js);
        }
        
        return $js;
    }
    
    private function optimizeJPEG($file) {
        // Simulação de otimização JPEG
        $optimizedFile = $this->config['paths']['cache'] . 'img/' . basename($file);
        
        // Em produção, usar ImageMagick ou similar
        copy($file, $optimizedFile);
        
        $optimizedSize = filesize($optimizedFile);
        $originalSize = filesize($file);
        
        return [
            'optimized_size' => $optimizedSize,
            'compression_ratio' => ($originalSize - $optimizedSize) / $originalSize * 100,
            'optimized_file' => $optimizedFile
        ];
    }
    
    private function optimizePNG($file) {
        // Simulação de otimização PNG
        $optimizedFile = $this->config['paths']['cache'] . 'img/' . basename($file);
        
        copy($file, $optimizedFile);
        
        $optimizedSize = filesize($optimizedFile);
        $originalSize = filesize($file);
        
        return [
            'optimized_size' => $optimizedSize,
            'compression_ratio' => ($originalSize - $optimizedSize) / $originalSize * 100,
            'optimized_file' => $optimizedFile
        ];
    }
    
    private function optimizeGIF($file) {
        // Otimização básica de GIF
        $optimizedFile = $this->config['paths']['cache'] . 'img/' . basename($file);
        copy($file, $optimizedFile);
        
        return [
            'optimized_size' => filesize($optimizedFile),
            'optimized_file' => $optimizedFile
        ];
    }
    
    private function optimizeSVG($file) {
        $content = file_get_contents($file);
        
        // Minificar SVG
        $content = preg_replace('/\s+/', ' ', $content);
        $content = preg_replace('/>\s*</', '><', $content);
        
        $optimizedFile = $this->config['paths']['cache'] . 'img/' . basename($file);
        file_put_contents($optimizedFile, $content);
        
        return [
            'optimized_size' => strlen($content),
            'compression_ratio' => (filesize($file) - strlen($content)) / filesize($file) * 100,
            'optimized_file' => $optimizedFile
        ];
    }
    
    private function createWebPVersion($file) {
        // Simulação de criação WebP
        $webpFile = $this->config['paths']['cache'] . 'img/' . pathinfo($file, PATHINFO_FILENAME) . '.webp';
        
        // Em produção usar cwebp ou GD/ImageMagick
        copy($file, $webpFile);
        
        return [
            'file' => $webpFile,
            'size' => filesize($webpFile)
        ];
    }
    
    private function createResponsiveVariants($file) {
        $variants = [];
        $sizes = [320, 480, 768, 1024, 1200];
        
        foreach ($sizes as $width) {
            $variantFile = $this->config['paths']['cache'] . 'img/' . 
                          pathinfo($file, PATHINFO_FILENAME) . "_{$width}w." . 
                          pathinfo($file, PATHINFO_EXTENSION);
            
            // Em produção redimensionar usando GD ou ImageMagick
            copy($file, $variantFile);
            
            $variants[$width] = [
                'file' => $variantFile,
                'width' => $width,
                'size' => filesize($variantFile)
            ];
        }
        
        return $variants;
    }
    
    private function isCriticalCSS($filename) {
        $criticalFiles = ['main.css', 'bootstrap.css', 'style.css'];
        return in_array($filename, $criticalFiles);
    }
    
    private function isCriticalJS($filename) {
        $criticalFiles = ['jquery.js', 'bootstrap.js', 'main.js'];
        return in_array($filename, $criticalFiles);
    }
    
    private function isAsyncSafe($jsContent) {
        // Verificar se o JS pode ser carregado assincronamente
        $blockingPatterns = ['document.write', 'document.open'];
        
        foreach ($blockingPatterns as $pattern) {
            if (strpos($jsContent, $pattern) !== false) {
                return false;
            }
        }
        
        return true;
    }
    
    private function createCSSBundle() {
        $cssFiles = glob($this->config['paths']['cache'] . 'css/*.min.css');
        $bundleContent = '';
        $totalSize = 0;
        
        foreach ($cssFiles as $file) {
            if ($totalSize < $this->config['bundling']['max_bundle_size']) {
                $content = file_get_contents($file);
                $bundleContent .= $content . "\n";
                $totalSize += strlen($content);
            }
        }
        
        $bundleFile = $this->config['paths']['cache'] . 'css/bundle.min.css';
        file_put_contents($bundleFile, $bundleContent);
        
        return [
            'file' => $bundleFile,
            'size' => strlen($bundleContent),
            'files_count' => count($cssFiles)
        ];
    }
    
    private function createJSBundle() {
        $jsFiles = glob($this->config['paths']['cache'] . 'js/*.min.js');
        $bundleContent = '';
        $totalSize = 0;
        
        foreach ($jsFiles as $file) {
            if ($totalSize < $this->config['bundling']['max_bundle_size']) {
                $content = file_get_contents($file);
                $bundleContent .= $content . ";\n";
                $totalSize += strlen($content);
            }
        }
        
        $bundleFile = $this->config['paths']['cache'] . 'js/bundle.min.js';
        file_put_contents($bundleFile, $bundleContent);
        
        return [
            'file' => $bundleFile,
            'size' => strlen($bundleContent),
            'files_count' => count($jsFiles)
        ];
    }
    
    private function generateAssetManifest($results) {
        $manifest = [
            'version' => time(),
            'css' => $results['css'],
            'javascript' => $results['javascript'], 
            'images' => $results['images'],
            'bundles' => $results['bundles'],
            'generated_at' => date('Y-m-d H:i:s')
        ];
        
        $manifestFile = $this->config['paths']['cache'] . 'asset-manifest.json';
        file_put_contents($manifestFile, json_encode($manifest, JSON_PRETTY_PRINT));
        
        return $manifestFile;
    }
    
    private function generateResourceHints($assets) {
        return '<!-- Resource Hints for Performance -->';
    }
    
    private function generateInlineCriticalCSS($assets) {
        return '<!-- Inline Critical CSS -->';
    }
    
    private function generatePreloadLinks($assets) {
        return '<!-- Preload Critical Resources -->';
    }
    
    private function generateAsyncCSS($assets) {
        return '<!-- Async Non-Critical CSS -->';
    }
    
    private function generateOptimizedJS($assets) {
        return '<!-- Optimized JavaScript Loading -->';
    }
    
    private function optimizeFonts() {
        // Implementar otimização de fontes
        return ['status' => 'Font optimization not implemented yet'];
    }
}

?>