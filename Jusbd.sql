-- Tabela para armazenar informações dos hóspedes
CREATE TABLE Hospedes (
    id_hospede INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    sobrenome VARCHAR(100) NOT NULL,
    cpf VARCHAR(14) UNIQUE NOT NULL,
    data_nascimento DATE,
    email VARCHAR(100) UNIQUE,
    telefone VARCHAR(20),
    endereco VARCHAR(255)
);

-- Tabela para armazenar os tipos de quarto disponíveis
CREATE TABLE Tipos_Quarto (
    id_tipo_quarto INT PRIMARY KEY AUTO_INCREMENT,
    nome_tipo VARCHAR(50) NOT NULL UNIQUE,
    descricao TEXT,
    capacidade INT NOT NULL,
    preco_diaria DECIMAL(10, 2) NOT NULL
);

-- Tabela para armazenar informações dos quartos
CREATE TABLE Quartos (
    id_quarto INT PRIMARY KEY AUTO_INCREMENT,
    numero_quarto VARCHAR(10) NOT NULL UNIQUE,
    id_tipo_quarto INT NOT NULL,
    status ENUM('Disponível', 'Ocupado', 'Manutenção', 'Limpeza') DEFAULT 'Disponível',
    andar INT,
    FOREIGN KEY (id_tipo_quarto) REFERENCES Tipos_Quarto(id_tipo_quarto)
);

-- Tabela para armazenar as reservas
CREATE TABLE Reservas (
    id_reserva INT PRIMARY KEY AUTO_INCREMENT,
    id_hospede INT NOT NULL,
    data_reserva TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_checkin_prevista DATE NOT NULL,
    data_checkout_prevista DATE NOT NULL,
    status_reserva ENUM('Confirmada', 'Pendente', 'Cancelada', 'Concluída') DEFAULT 'Confirmada',
    observacoes TEXT,
    FOREIGN KEY (id_hospede) REFERENCES Hospedes(id_hospede)
);

-- Tabela de relacionamento entre Reservas e Quartos (uma reserva pode ter múltiplos quartos)
CREATE TABLE Reservas_Quartos (
    id_reserva_quarto INT PRIMARY KEY AUTO_INCREMENT,
    id_reserva INT NOT NULL,
    id_quarto INT NOT NULL,
    preco_diaria_aplicado DECIMAL(10, 2) NOT NULL, -- Preço da diária no momento da reserva
    FOREIGN KEY (id_reserva) REFERENCES Reservas(id_reserva),
    FOREIGN KEY (id_quarto) REFERENCES Quartos(id_quarto),
    UNIQUE (id_reserva, id_quarto) -- Garante que um quarto não seja reservado duas vezes na mesma reserva
);


-- Tabela para registrar os check-ins e check-outs efetivos (a estadia)
CREATE TABLE Estadias (
    id_estadia INT PRIMARY KEY AUTO_INCREMENT,
    id_reserva INT NOT NULL,
    id_hospede INT NOT NULL, -- Pode ser redundante se a reserva já tem, mas útil para relatórios rápidos
    id_quarto INT NOT NULL, -- O quarto efetivamente usado para o check-in
    data_checkin_real DATE NOT NULL,
    data_checkout_real DATE,
    valor_total_pago DECIMAL(10, 2),
    status_pagamento ENUM('Pendente', 'Pago', 'Parcialmente Pago', 'Estornado') DEFAULT 'Pendente',
    FOREIGN KEY (id_reserva) REFERENCES Reservas(id_reserva),
    FOREIGN KEY (id_hospede) REFERENCES Hospedes(id_hospede),
    FOREIGN KEY (id_quarto) REFERENCES Quartos(id_quarto)
);
