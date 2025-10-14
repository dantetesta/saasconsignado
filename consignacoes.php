<?php
/**
 * Gerenciamento de Consignações
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.0.0 SaaS
 */

require_once 'config/config.php';
requireLogin();

$pageTitle = 'Consignações';
$db = Database::getInstance()->getConnection();

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $estabelecimento_id = intval($_POST['estabelecimento_id']);
        $tipo = $_POST['tipo'] ?? 'pontual'; // Novo campo
        $data_consignacao = $_POST['data_consignacao'];
        $data_vencimento = $_POST['data_vencimento'] ?? null;
        $observacoes = sanitize($_POST['observacoes']);
        $produtos = $_POST['produtos'] ?? [];
        $quantidades = $_POST['quantidades'] ?? [];
        
        try {
            // Verificar limite do plano (SaaS)
            TenantMiddleware::canCreateConsignacao($estabelecimento_id);
            
            $db->beginTransaction();
            
            // Inserir consignação com tenant_id
            $tenant_id = getTenantId();
            $stmt = $db->prepare("INSERT INTO consignacoes (tenant_id, estabelecimento_id, tipo, data_consignacao, data_vencimento, observacoes, status) VALUES (?, ?, ?, ?, ?, ?, 'pendente')");
            $stmt->execute([$tenant_id, $estabelecimento_id, $tipo, $data_consignacao, $data_vencimento, $observacoes]);
            $consignacao_id = $db->lastInsertId();
            
            // Inserir itens
            foreach ($produtos as $index => $produto_id) {
                $quantidade = intval($quantidades[$index]);
                if ($quantidade > 0) {
                    // Buscar preço e estoque do produto (do tenant)
                    $stmt = $db->prepare("SELECT preco_venda, estoque_total FROM produtos WHERE id = ? AND tenant_id = ?");
                    $stmt->execute([$produto_id, $tenant_id]);
                    $produto = $stmt->fetch();
                    $preco = $produto['preco_venda'];
                    $estoque_atual = $produto['estoque_total'];
                    
                    // Validar estoque disponível
                    if ($estoque_atual < $quantidade) {
                        throw new Exception("Estoque insuficiente para o produto ID {$produto_id}. Disponível: {$estoque_atual}, Solicitado: {$quantidade}");
                    }
                    
                    if ($tipo === 'continua') {
                        // Para consignações contínuas, criar movimentação de entrega inicial
                        $stmt = $db->prepare("INSERT INTO movimentacoes_consignacao (tenant_id, consignacao_id, produto_id, tipo, quantidade, preco_unitario, data_movimentacao, observacoes) VALUES (?, ?, ?, 'entrega', ?, ?, ?, 'Entrega inicial')");
                        $stmt->execute([$tenant_id, $consignacao_id, $produto_id, $quantidade, $preco, $data_consignacao]);
                        
                        // Baixar do estoque (apenas produtos do tenant)
                        $stmt = $db->prepare("UPDATE produtos SET estoque_total = estoque_total - ? WHERE id = ? AND tenant_id = ?");
                        $stmt->execute([$quantidade, $produto_id, $tenant_id]);
                    } else {
                        // Para consignações pontuais, usar tabela normal
                        $stmt = $db->prepare("INSERT INTO consignacao_itens (tenant_id, consignacao_id, produto_id, quantidade_consignada, preco_unitario) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$tenant_id, $consignacao_id, $produto_id, $quantidade, $preco]);
                        
                        // Baixar do estoque (apenas produtos do tenant)
                        $stmt = $db->prepare("UPDATE produtos SET estoque_total = estoque_total - ? WHERE id = ? AND tenant_id = ?");
                        $stmt->execute([$quantidade, $produto_id, $tenant_id]);
                    }
                }
            }
            
            $db->commit();
            setFlashMessage('success', 'Consignação criada com sucesso!');
            redirect('/consignacoes.php');
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Erro ao criar consignação: " . $e->getMessage());
            setFlashMessage('error', $e->getMessage());
        }
    } elseif ($action === 'update_status') {
        // Log temporário para debug
        error_log("=== UPDATE STATUS RECEBIDO ===");
        error_log("POST completo: " . print_r($_POST, true));
        
        $consignacao_id = intval($_POST['consignacao_id']);
        $itens = $_POST['itens'] ?? [];
        $vendidos = $_POST['vendidos'] ?? [];
        $devolvidos = $_POST['devolvidos'] ?? [];
        
        error_log("Consignação ID: $consignacao_id");
        error_log("Itens: " . print_r($itens, true));
        error_log("Vendidos: " . print_r($vendidos, true));
        error_log("Devolvidos: " . print_r($devolvidos, true));
        
        try {
            $db->beginTransaction();
            
            foreach ($itens as $index => $item_id) {
                $quantidade_vendida = intval($vendidos[$index] ?? 0);
                $quantidade_devolvida = intval($devolvidos[$index] ?? 0);
                
                $stmt = $db->prepare("UPDATE consignacao_itens SET quantidade_vendida = ?, quantidade_devolvida = ? WHERE id = ?");
                $stmt->execute([$quantidade_vendida, $quantidade_devolvida, $item_id]);
            }
            
            // Atualizar status da consignação
            $stmt = $db->prepare("
                SELECT 
                    SUM(quantidade_consignada) as total_consignado,
                    SUM(quantidade_vendida + quantidade_devolvida) as total_processado
                FROM consignacao_itens
                WHERE consignacao_id = ?
            ");
            $stmt->execute([$consignacao_id]);
            $totais = $stmt->fetch();
            
            $status = 'pendente';
            if ($totais['total_processado'] >= $totais['total_consignado']) {
                $status = 'finalizada';
            } elseif ($totais['total_processado'] > 0) {
                $status = 'parcial';
            }
            
            $stmt = $db->prepare("UPDATE consignacoes SET status = ? WHERE id = ?");
            $stmt->execute([$status, $consignacao_id]);
            
            error_log("Novo status calculado: $status");
            error_log("Total consignado: {$totais['total_consignado']}, Total processado: {$totais['total_processado']}");
            
            $db->commit();
            error_log("✓ COMMIT realizado com sucesso!");
            
            setFlashMessage('success', 'Consignação atualizada com sucesso!');
            redirect('/consignacoes.php?action=view&id=' . $consignacao_id);
        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Erro ao atualizar consignação: " . $e->getMessage());
            setFlashMessage('error', 'Erro ao atualizar consignação: ' . $e->getMessage());
            redirect('/consignacoes.php?action=update&id=' . $consignacao_id);
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        
        try {
            $db->beginTransaction();
            
            // Buscar tipo da consignação (do tenant)
            $tenant_id = getTenantId();
            $stmt = $db->prepare("SELECT tipo FROM consignacoes WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$id, $tenant_id]);
            $consignacao = $stmt->fetch();
            
            if ($consignacao['tipo'] === 'pontual') {
                // Para pontuais: devolver ao estoque apenas o que ainda está consignado
                $stmt = $db->prepare("
                    SELECT produto_id, 
                           (quantidade_consignada - quantidade_vendida - quantidade_devolvida) as quantidade_devolver
                    FROM consignacao_itens 
                    WHERE consignacao_id = ?
                ");
                $stmt->execute([$id]);
                $itens = $stmt->fetchAll();
                
                foreach ($itens as $item) {
                    if ($item['quantidade_devolver'] > 0) {
                        $stmt = $db->prepare("UPDATE produtos SET estoque_total = estoque_total + ? WHERE id = ?");
                        $stmt->execute([$item['quantidade_devolver'], $item['produto_id']]);
                    }
                }
            } else {
                // Para contínuas: devolver o saldo atual (entregas - vendas - devoluções)
                $stmt = $db->prepare("
                    SELECT produto_id,
                           SUM(CASE WHEN tipo = 'entrega' THEN quantidade ELSE 0 END) -
                           SUM(CASE WHEN tipo = 'venda' THEN quantidade ELSE 0 END) -
                           SUM(CASE WHEN tipo = 'devolucao' THEN quantidade ELSE 0 END) as quantidade_devolver
                    FROM movimentacoes_consignacao
                    WHERE consignacao_id = ?
                    GROUP BY produto_id
                ");
                $stmt->execute([$id]);
                $itens = $stmt->fetchAll();
                
                foreach ($itens as $item) {
                    if ($item['quantidade_devolver'] > 0) {
                        $stmt = $db->prepare("UPDATE produtos SET estoque_total = estoque_total + ? WHERE id = ?");
                        $stmt->execute([$item['quantidade_devolver'], $item['produto_id']]);
                    }
                }
            }
            
            // Deletar pagamentos
            $stmt = $db->prepare("DELETE FROM pagamentos WHERE consignacao_id = ?");
            $stmt->execute([$id]);
            
            // Deletar itens
            $stmt = $db->prepare("DELETE FROM consignacao_itens WHERE consignacao_id = ?");
            $stmt->execute([$id]);
            
            // Deletar movimentações (se for contínua)
            $stmt = $db->prepare("DELETE FROM movimentacoes_consignacao WHERE consignacao_id = ?");
            $stmt->execute([$id]);
            
            // Deletar consignação
            $stmt = $db->prepare("DELETE FROM consignacoes WHERE id = ?");
            $stmt->execute([$id]);
            
            $db->commit();
            setFlashMessage('success', 'Consignação deletada com sucesso!');
            
            // Manter filtros na URL
            $redirect_url = '/consignacoes.php';
            $params = [];
            
            if (!empty($_POST['status_filter'])) {
                $params[] = 'status=' . urlencode($_POST['status_filter']);
            }
            if (!empty($_POST['tipo_filter'])) {
                $params[] = 'tipo=' . urlencode($_POST['tipo_filter']);
            }
            
            if (!empty($params)) {
                $redirect_url .= '?' . implode('&', $params);
            }
            
            redirect($redirect_url);
        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Erro ao deletar consignação: " . $e->getMessage());
            setFlashMessage('error', 'Erro ao deletar consignação.');
        }
    } elseif ($action === 'delete_movimentacao_view') {
        $movimentacao_id = intval($_POST['movimentacao_id']);
        $consignacao_id = intval($_POST['consignacao_id']);
        
        try {
            $db->beginTransaction();
            
            // Buscar dados da movimentação antes de deletar
            $stmt = $db->prepare("SELECT produto_id, tipo, quantidade FROM movimentacoes_consignacao WHERE id = ?");
            $stmt->execute([$movimentacao_id]);
            $mov = $stmt->fetch();
            
            if ($mov) {
                // Reverter o estoque
                if ($mov['tipo'] === 'entrega') {
                    // Entrega foi deletada: devolve ao estoque
                    $stmt = $db->prepare("UPDATE produtos SET estoque_total = estoque_total + ? WHERE id = ?");
                    $stmt->execute([$mov['quantidade'], $mov['produto_id']]);
                } elseif ($mov['tipo'] === 'devolucao') {
                    // Devolução foi deletada: verificar se tem estoque suficiente
                    $stmt = $db->prepare("SELECT estoque_total FROM produtos WHERE id = ?");
                    $stmt->execute([$mov['produto_id']]);
                    $estoque_atual = $stmt->fetchColumn();
                    
                    if ($estoque_atual < $mov['quantidade']) {
                        throw new Exception("Não é possível deletar esta devolução. Estoque insuficiente para reverter.");
                    }
                    
                    // Remove do estoque
                    $stmt = $db->prepare("UPDATE produtos SET estoque_total = estoque_total - ? WHERE id = ?");
                    $stmt->execute([$mov['quantidade'], $mov['produto_id']]);
                }
                // Venda deletada: não altera estoque
                
                // Deletar movimentação
                $stmt = $db->prepare("DELETE FROM movimentacoes_consignacao WHERE id = ?");
                $stmt->execute([$movimentacao_id]);
            }
            
            $db->commit();
            setFlashMessage('success', 'Movimentação deletada com sucesso!');
            redirect('/consignacoes.php?action=view&id=' . $consignacao_id);
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Erro ao deletar movimentação: " . $e->getMessage());
            setFlashMessage('error', $e->getMessage());
        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Erro ao deletar movimentação: " . $e->getMessage());
            setFlashMessage('error', 'Erro ao deletar movimentação.');
        }
    } elseif ($action === 'register_payment') {
        $consignacao_id = intval($_POST['consignacao_id']);
        $data_pagamento = $_POST['data_pagamento'];
        $valor_pago = floatval($_POST['valor_pago']);
        $forma_pagamento = $_POST['forma_pagamento'];
        $observacoes = sanitize($_POST['observacoes_pagamento']);
        
        try {
            // Incluir tenant_id no pagamento
            $tenant_id = getTenantId();
            $stmt = $db->prepare("INSERT INTO pagamentos (tenant_id, consignacao_id, data_pagamento, valor_pago, forma_pagamento, observacoes) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$tenant_id, $consignacao_id, $data_pagamento, $valor_pago, $forma_pagamento, $observacoes]);
            
            // Atualizar status automático
            atualizarStatusAutomatico($db, $consignacao_id);
            
            setFlashMessage('success', 'Pagamento registrado com sucesso!');
            redirect('/consignacoes.php?action=view&id=' . $consignacao_id);
        } catch (PDOException $e) {
            error_log("Erro ao registrar pagamento: " . $e->getMessage());
            setFlashMessage('error', 'Erro ao registrar pagamento.');
        }
    }
}

// Determinar ação
$currentAction = $_GET['action'] ?? 'list';
$consignacaoId = $_GET['id'] ?? null;

// Verificar se é consignação contínua tentando acessar update
if ($currentAction === 'update' && $consignacaoId) {
    $tenant_id = getTenantId();
    $stmt = $db->prepare("SELECT tipo FROM consignacoes WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$consignacaoId, $tenant_id]);
    $consig = $stmt->fetch();
    
    if ($consig && $consig['tipo'] === 'continua') {
        setFlashMessage('info', 'Esta é uma consignação contínua. Use a página de movimentações para gerenciar produtos.');
        redirect('/movimentacoes.php?consignacao_id=' . $consignacaoId);
    }
}

include 'includes/header.php';

// Incluir a view apropriada
if ($currentAction === 'new') {
    include 'views/consignacoes_form.php';
} elseif ($currentAction === 'view' && $consignacaoId) {
    include 'views/consignacoes_view.php';
} elseif ($currentAction === 'update' && $consignacaoId) {
    include 'views/consignacoes_update.php';
} else {
    include 'views/consignacoes_list.php';
}

include 'includes/footer.php';
?>
