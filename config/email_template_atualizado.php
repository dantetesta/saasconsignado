<?php
/**
 * TEMPLATE DE EMAIL ATUALIZADO - Versão 1.2.2
 * 
 * INSTRUÇÕES:
 * 1. Copie a função getEmailTemplate() deste arquivo
 * 2. Cole no seu arquivo config/email.php (substituindo a função antiga)
 * 3. O botão ficará com melhor contraste e design profissional
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 1.2.2
 */

/**
 * Template HTML para email de notificação de consignação
 * VERSÃO ATUALIZADA COM BOTÃO MELHORADO
 */
function getEmailTemplate($dados) {
    $html = '
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Nova Consignação</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
                background-color: #f5f5f5;
            }
            .container {
                background: white;
                border-radius: 10px;
                overflow: hidden;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .header {
                background: linear-gradient(135deg, #9333ea 0%, #ec4899 100%);
                color: white;
                padding: 30px 20px;
                text-align: center;
            }
            .header h1 {
                margin: 0;
                font-size: 24px;
            }
            .content {
                padding: 30px 20px;
            }
            .info-box {
                background: #f9fafb;
                border-left: 4px solid #9333ea;
                padding: 15px;
                margin: 20px 0;
                border-radius: 4px;
            }
            .info-box h3 {
                margin: 0 0 10px 0;
                color: #9333ea;
                font-size: 16px;
            }
            .product-list {
                list-style: none;
                padding: 0;
                margin: 15px 0;
            }
            .product-list li {
                padding: 10px;
                border-bottom: 1px solid #e5e7eb;
                display: flex;
                justify-content: space-between;
            }
            .product-list li:last-child {
                border-bottom: none;
            }
            .button {
                display: inline-block;
                background: #ffffff;
                color: #9333ea;
                text-decoration: none;
                padding: 16px 40px;
                border-radius: 12px;
                font-weight: bold;
                text-align: center;
                margin: 20px 0;
                border: 3px solid #9333ea;
                font-size: 16px;
                transition: all 0.3s ease;
            }
            .button:hover {
                background: #9333ea;
                color: #ffffff;
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(147, 51, 234, 0.3);
            }
            .button-container {
                text-align: center;
                padding: 10px 0;
            }
            .footer {
                background: #f9fafb;
                padding: 20px;
                text-align: center;
                font-size: 12px;
                color: #6b7280;
            }
            .total {
                background: #fef3c7;
                padding: 15px;
                border-radius: 8px;
                margin: 20px 0;
                font-size: 18px;
                font-weight: bold;
                text-align: center;
            }
            @media only screen and (max-width: 600px) {
                .button {
                    display: block;
                    width: 100%;
                    padding: 14px 20px;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🍿 Nova Consignação</h1>
                <p>Consignação #' . $dados['consignacao_id'] . '</p>
            </div>
            
            <div class="content">
                <p>Olá, <strong>' . $dados['estabelecimento'] . '</strong>!</p>
                
                <p>Uma nova consignação foi criada para o seu estabelecimento.</p>
                
                <div class="info-box">
                    <h3>📅 Informações da Consignação</h3>
                    <p><strong>Data:</strong> ' . $dados['data_consignacao'] . '</p>
                    ' . (!empty($dados['data_vencimento']) ? '<p><strong>Vencimento:</strong> ' . $dados['data_vencimento'] . '</p>' : '') . '
                </div>
                
                <div class="info-box">
                    <h3>📦 Produtos Consignados</h3>
                    <ul class="product-list">
                        ' . $dados['produtos_html'] . '
                    </ul>
                </div>
                
                <div class="total">
                    💰 Valor Total: ' . $dados['valor_total'] . '
                </div>
                
                <div class="button-container">
                    <a href="' . $dados['link_consulta'] . '" class="button">
                        🔗 Acompanhar Consignação
                    </a>
                </div>
                
                <div class="info-box">
                    <h3>🔐 Como Acessar</h3>
                    <p>1. Clique no botão acima</p>
                    <p>2. Digite sua senha de acesso</p>
                    <p>3. Acompanhe em tempo real!</p>
                </div>
                
                ' . (!empty($dados['observacoes']) ? '
                <div class="info-box">
                    <h3>📝 Observações</h3>
                    <p>' . nl2br($dados['observacoes']) . '</p>
                </div>
                ' : '') . '
            </div>
            
            <div class="footer">
                <p>Este é um email automático do Sistema de Consignados</p>
                <p>Desenvolvido por <a href="https://dantetesta.com.br" style="color: #9333ea;">Dante Testa</a></p>
                <p>Versão 1.2.2</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    return $html;
}
