<?php
// Configurações do Banco de Dados
$host = "127.0.0.1";
$usuario = "root";
$senha = "";
$banco = "chamados";

$conn = new mysqli($host, $usuario, $senha, $banco);

if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// AÇÃO: Atualizar o Status do Chamado
if (isset($_GET['action']) && $_GET['action'] == 'update_status') {
    $id = intval($_GET['id']);
    $novo_status = $_GET['status'];
    
    $stmt = $conn->prepare("UPDATE chamados SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $novo_status, $id);
    $stmt->execute();
    $stmt->close();
    
    // Redireciona para limpar os parâmetros da URL
    header("Location: painel.php");
    exit;
}

// Busca todos os chamados ordenados pelos mais recentes
$sql = "SELECT * FROM chamados ORDER BY data_criacao DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Chamados - Admin</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 30px;
        }
        .container {
            max-width: 1100px;
            margin: 0 auto;
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        h1 {
            color: #0056b3;
            margin-top: 0;
            border-bottom: 2px solid #f4f6f9;
            padding-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-atualizar {
            background-color: #0056b3;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
        }
        .btn-atualizar:hover {
            background-color: #003d82;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
        }
        th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 600;
        }
        tr:hover {
            background-color: #fcfcfc;
        }
        /* Badges de Prioridade */
        .badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }
        .badge-alta { background-color: #f8d7da; color: #721c24; }
        .badge-media { background-color: #fff3cd; color: #856404; }
        .badge-baixa { background-color: #d4edda; color: #155724; }
        
        /* Cores de Status */
        .status-select {
            padding: 6px;
            border-radius: 4px;
            border: 1px solid #ccc;
            background-color: #fff;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>
        Painel de Gerenciamento de Chamados
        <a href="painel.php" class="btn-atualizar">🔄 Atualizar Painel</a>
    </h1>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Data</th>
                <th>Usuário</th>
                <th>Contato</th>
                <th>Categoria</th>
                <th>Prioridade</th>
                <th>Descrição</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <?php 
                        // Define a classe da badge de prioridade
                        $prioridade_classe = 'badge-baixa';
                        if (strtolower($row['prioridade']) == 'alta') {
                            $prioridade_classe = 'badge-alta';
                        } elseif (strtolower($row['prioridade']) == 'média' || strtolower($row['prioridade']) == 'media') {
                            $prioridade_classe = 'badge-media';
                        }
                    ?>
                    <tr>
                        <td><strong>#<?php echo $row['id']; ?></strong></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($row['data_criacao'])); ?></td>
                        <td><?php echo htmlspecialchars($row['nome']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['categoria']); ?></td>
                        <td>
                            <span class="badge <?php echo $prioridade_classe; ?>">
                                <?php echo htmlspecialchars($row['prioridade']); ?>
                            </span>
                        </td>
                        <td><?php echo nl2br(htmlspecialchars($row['descricao'])); ?></td>
                        <td>
                            <!-- Dropdown para alterar o status dinamicamente -->
                            <select class="status-select" onchange="alterarStatus(<?php echo $row['id']; ?>, this.value)">
                                <option value="Pendente" <?php echo $row['status'] == 'Pendente' ? 'selected' : ''; ?>>Pendente</option>
                                <option value="Em Atendimento" <?php echo $row['status'] == 'Em Atendimento' ? 'selected' : ''; ?>>Em Atendimento</option>
                                <option value="Resolvido" <?php echo $row['status'] == 'Resolvido' ? 'selected' : ''; ?>>Resolvido</option>
                            </select>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align: center; color: #777; padding: 30px;">
                        Nenhum chamado registrado no momento.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
    // Função que redireciona aplicando a alteração de status no banco de dados
    function alterarStatus(id, novoStatus) {
        if(confirm("Deseja alterar o status do chamado #" + id + " para '" + novoStatus + "'?")) {
            window.location.href = "painel.php?action=update_status&id=" + id + "&status=" + encodeURIComponent(novoStatus);
        } else {
            // Se cancelar, recarrega a página para resetar a seleção visual do dropdown
            window.location.reload();
        }
    }
</script>

</body>
</html>
<?php $conn->close(); ?>
