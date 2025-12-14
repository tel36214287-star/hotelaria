<?php
header('Content-Type: application/json');

// 1. Configuração do banco de dados
$host = "localhost";
$user = "root";      // usuário do MySQL
$password = "";      // senha do MySQL
$dbname = "hotelaria"; // nome do banco de dados

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(["erro" => "Falha na conexão: " . $conn->connect_error]));
}

// 2. Determinar ação via GET ou POST
$acao = isset($_GET['acao']) ? $_GET['acao'] : 'listar_quartos';

// 3. Funções
function listar_quartos($conn) {
    $sql = "SELECT q.id_quarto, q.numero_quarto, t.nome_tipo, t.capacidade, t.preco_diaria, q.status,
            e.id_estadia, e.data_checkin_real, e.data_checkout_real, h.nome AS hospede_nome
            FROM Quartos q
            LEFT JOIN Tipos_Quarto t ON q.id_tipo_quarto = t.id_tipo_quarto
            LEFT JOIN Estadias e ON q.id_quarto = e.id_quarto AND e.status_pagamento IN ('Pendente','Pago','Parcialmente Pago')
            LEFT JOIN Hospedes h ON e.id_hospede = h.id_hospede";

    $result = $conn->query($sql);
    $quartos = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $quartos[] = [
                "id_quarto" => $row['id_quarto'],
                "numero_quarto" => $row['numero_quarto'],
                "tipo" => $row['nome_tipo'],
                "capacidade" => $row['capacidade'],
                "preco_diaria" => $row['preco_diaria'],
                "status" => $row['status'] == 'Disponível' ? 'VAGO' : 'OCUPADO',
                "hospede_nome" => $row['hospede_nome'],
                "check_in" => $row['data_checkin_real'],
                "check_out" => $row['data_checkout_real']
            ];
        }
    }
    echo json_encode($quartos);
}

function checkin($conn) {
    $nome = $_POST['nome'];
    $sobrenome = $_POST['sobrenome'];
    $cpf = $_POST['cpf'];
    $quarto_id = $_POST['quarto_id'];
    $checkin_data = $_POST['data_checkin'];
    $checkout_data = $_POST['data_checkout'];

    // 1. Inserir hóspede
    $stmt = $conn->prepare("INSERT INTO Hospedes (nome, sobrenome, cpf) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $nome, $sobrenome, $cpf);
    $stmt->execute();
    $hospede_id = $stmt->insert_id;
    $stmt->close();

    // 2. Criar reserva
    $stmt = $conn->prepare("INSERT INTO Reservas (id_hospede, data_checkin_prevista, data_checkout_prevista) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $hospede_id, $checkin_data, $checkout_data);
    $stmt->execute();
    $reserva_id = $stmt->insert_id;
    $stmt->close();

    // 3. Associar quarto à reserva
    $stmt = $conn->prepare("INSERT INTO Reservas_Quartos (id_reserva, id_quarto, preco_diaria_aplicado) VALUES (?, ?, (SELECT preco_diaria FROM Quartos q JOIN Tipos_Quarto t ON q.id_tipo_quarto=t.id_tipo_quarto WHERE q.id_quarto=?))");
    $stmt->bind_param("iii", $reserva_id, $quarto_id, $quarto_id);
    $stmt->execute();
    $stmt->close();

    // 4. Criar estadia
    $stmt = $conn->prepare("INSERT INTO Estadias (id_reserva, id_hospede, id_quarto, data_checkin_real, status_pagamento) VALUES (?, ?, ?, ?, 'Pendente')");
    $stmt->bind_param("iiis", $reserva_id, $hospede_id, $quarto_id, $checkin_data);
    $stmt->execute();
    $stmt->close();

    // 5. Atualizar status do quarto
    $stmt = $conn->prepare("UPDATE Quartos SET status='Ocupado' WHERE id_quarto=?");
    $stmt->bind_param("i", $quarto_id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(["sucesso" => "Check-in realizado com sucesso!"]);
}

function checkout($conn) {
    $quarto_id = $_POST['quarto_id'];
    $data_checkout = $_POST['data_checkout'];
    $valor_pago = $_POST['valor_pago'];

    // Atualiza estadia
    $stmt = $conn->prepare("UPDATE Estadias SET data_checkout_real=?, valor_total_pago=?, status_pagamento='Pago' WHERE id_quarto=? AND status_pagamento='Pendente'");
    $stmt->bind_param("sdi", $data_checkout, $valor_pago, $quarto_id);
    $stmt->execute();
    $stmt->close();

    // Atualiza status do quarto
    $stmt = $conn->prepare("UPDATE Quartos SET status='Disponível' WHERE id_quarto=?");
    $stmt->bind_param("i", $quarto_id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(["sucesso" => "Check-out realizado com sucesso!"]);
}

// 4. Chama a função correta
switch($acao) {
    case "checkin":
        checkin($conn);
        break;
    case "checkout":
        checkout($conn);
        break;
    case "listar_quartos":
    default:
        listar_quartos($conn);
        break;
}

$conn->close();
?>
