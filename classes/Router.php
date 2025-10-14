<?php
/**
 * Router - Sistema de Roteamento Avançado com URLs Semânticas
 * 
 * Gerencia rotas amigáveis e SEO-friendly para o sistema
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 1.0.0
 */

class Router {
    private $routes = [];
    private $namedRoutes = [];
    private $currentRoute = null;
    
    /**
     * Adiciona uma rota GET
     * 
     * @param string $pattern Padrão da URL (ex: /produto/{id})
     * @param callable|string $callback Função ou arquivo a executar
     * @param string|null $name Nome da rota para geração de URLs
     */
    public function get($pattern, $callback, $name = null) {
        $this->addRoute('GET', $pattern, $callback, $name);
    }
    
    /**
     * Adiciona uma rota POST
     */
    public function post($pattern, $callback, $name = null) {
        $this->addRoute('POST', $pattern, $callback, $name);
    }
    
    /**
     * Adiciona uma rota que aceita GET e POST
     */
    public function any($pattern, $callback, $name = null) {
        $this->addRoute('GET|POST', $pattern, $callback, $name);
    }
    
    /**
     * Adiciona uma rota ao sistema
     */
    private function addRoute($method, $pattern, $callback, $name = null) {
        $route = [
            'method' => $method,
            'pattern' => $pattern,
            'callback' => $callback,
            'params' => []
        ];
        
        $this->routes[] = $route;
        
        if ($name) {
            $this->namedRoutes[$name] = $pattern;
        }
    }
    
    /**
     * Executa o roteamento
     */
    public function dispatch() {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove trailing slash (exceto para raiz)
        if ($requestUri !== '/' && substr($requestUri, -1) === '/') {
            $requestUri = rtrim($requestUri, '/');
        }
        
        foreach ($this->routes as $route) {
            // Verifica se o método HTTP corresponde
            if (!preg_match('/^(' . $route['method'] . ')$/i', $requestMethod)) {
                continue;
            }
            
            // Converte o padrão em regex
            $pattern = $this->convertPatternToRegex($route['pattern']);
            
            // Verifica se a URL corresponde ao padrão
            if (preg_match($pattern, $requestUri, $matches)) {
                array_shift($matches); // Remove o primeiro elemento (match completo)
                
                $this->currentRoute = $route;
                $route['params'] = $matches;
                
                return $this->executeCallback($route['callback'], $matches);
            }
        }
        
        // Nenhuma rota encontrada - 404
        $this->handle404();
    }
    
    /**
     * Converte padrão de rota em regex
     * 
     * Exemplos:
     * /produto/{id} -> /^\/produto\/([^\/]+)$/
     * /produto/{id}/editar -> /^\/produto\/([^\/]+)\/editar$/
     * /relatorio/{tipo}/{ano}/{mes} -> /^\/relatorio\/([^\/]+)\/([^\/]+)\/([^\/]+)$/
     */
    private function convertPatternToRegex($pattern) {
        // Escapa barras
        $pattern = str_replace('/', '\/', $pattern);
        
        // Converte {param} em regex
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^\/]+)', $pattern);
        
        return '/^' . $pattern . '$/';
    }
    
    /**
     * Executa o callback da rota
     */
    private function executeCallback($callback, $params) {
        if (is_callable($callback)) {
            // Se for uma função, executa diretamente
            return call_user_func_array($callback, $params);
        } elseif (is_string($callback)) {
            // Se for um arquivo PHP, inclui ele
            if (file_exists($callback)) {
                // Disponibiliza os parâmetros para o arquivo
                extract($this->getNamedParams($params));
                require $callback;
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Converte parâmetros posicionais em nomeados
     */
    private function getNamedParams($params) {
        if (!$this->currentRoute) {
            return [];
        }
        
        $pattern = $this->currentRoute['pattern'];
        preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $pattern, $matches);
        
        $namedParams = [];
        foreach ($matches[1] as $index => $name) {
            if (isset($params[$index])) {
                $namedParams[$name] = $params[$index];
            }
        }
        
        return $namedParams;
    }
    
    /**
     * Gera URL a partir do nome da rota
     * 
     * @param string $name Nome da rota
     * @param array $params Parâmetros para substituir
     * @return string URL gerada
     */
    public function url($name, $params = []) {
        if (!isset($this->namedRoutes[$name])) {
            return '#';
        }
        
        $url = $this->namedRoutes[$name];
        
        foreach ($params as $key => $value) {
            $url = str_replace('{' . $key . '}', $value, $url);
        }
        
        return $url;
    }
    
    /**
     * Trata erro 404
     */
    private function handle404() {
        http_response_code(404);
        
        // Verifica se existe página 404 personalizada
        if (file_exists(__DIR__ . '/../404.php')) {
            require __DIR__ . '/../404.php';
        } else {
            echo '<h1>404 - Página não encontrada</h1>';
            echo '<p>A página que você procura não existe.</p>';
            echo '<a href="/">Voltar para o início</a>';
        }
        
        exit;
    }
    
    /**
     * Middleware para verificar autenticação
     */
    public function requireAuth() {
        if (!isLoggedIn()) {
            redirect('/login');
            exit;
        }
    }
    
    /**
     * Middleware para verificar se é admin
     */
    public function requireAdmin() {
        $this->requireAuth();
        
        if (!isSuperAdmin()) {
            http_response_code(403);
            echo '<h1>403 - Acesso Negado</h1>';
            echo '<p>Você não tem permissão para acessar esta página.</p>';
            exit;
        }
    }
}
