<?php

class Database {
    private $host = "";
    private $dbname = "";
    private $username = "";
    private $password = "";
    private $pdo;
	
//-------------------------------------------------------------------------------------------------------------------------------------------|
// |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ GetReady ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~|
//-------------------------------------------------------------------------------------------------------------------------------------------|

    public function __construct() {
        $this->connect();
        $this->initializeDatabase();
    }

    private function connect() {
        try {
            $this->pdo = new PDO("mysql:host={$this->host};dbname={$this->dbname};charset=utf8",$this->username,$this->password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {die("Erreur de connexion : " . $e->getMessage());}
    }

    private function initializeDatabase() {
        try {
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS roles (id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(50) NOT NULL)");
			$this->pdo->exec("CREATE TABLE IF NOT EXISTS users (id INT AUTO_INCREMENT PRIMARY KEY,first_name VARCHAR(50) NOT NULL,last_name VARCHAR(50) NOT NULL,email VARCHAR(100) NOT NULL UNIQUE,password VARCHAR(100) NOT NULL,role_id INT,FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL)");
			$this->pdo->exec("CREATE TABLE IF NOT EXISTS clients (id INT AUTO_INCREMENT PRIMARY KEY,first_name VARCHAR(50) NOT NULL,last_name VARCHAR(50) NOT NULL,email VARCHAR(100),phone VARCHAR(20),address VARCHAR(255))");
			$this->pdo->exec("CREATE TABLE IF NOT EXISTS gmail_tokens (id INT AUTO_INCREMENT PRIMARY KEY,access_token TEXT,refresh_token TEXT,expires_at INT,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
			$this->pdo->exec("CREATE TABLE IF NOT EXISTS gmail_messages (id INT AUTO_INCREMENT PRIMARY KEY,gmail_id VARCHAR(255) UNIQUE,client_id INT,sender_email VARCHAR(255),receiver_email VARCHAR(255),subject TEXT,body LONGTEXT,direction VARCHAR(20),created_at DATETIME,thread_id VARCHAR(255) NULL,message_id_header VARCHAR(255) NULL,FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL)");
			$this->pdo->exec("CREATE TABLE IF NOT EXISTS tickets (id INT AUTO_INCREMENT PRIMARY KEY,client_id INT NOT NULL,gmail_message_id INT NULL,title VARCHAR(255) NOT NULL,issue_type VARCHAR(100) DEFAULT NULL,priority VARCHAR(50) DEFAULT 'normal',status VARCHAR(50) DEFAULT 'open',ai_summary TEXT NULL,ai_confidence DECIMAL(5,2) NULL,claimed_by INT NULL,claimed_at DATETIME NULL,solved_by INT NULL,solved_at DATETIME NULL,resolution_time_minutes INT NULL,created_at DATETIME DEFAULT CURRENT_TIMESTAMP,updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,FOREIGN KEY (gmail_message_id) REFERENCES gmail_messages(id) ON DELETE SET NULL,FOREIGN KEY (claimed_by) REFERENCES users(id) ON DELETE SET NULL,FOREIGN KEY (solved_by) REFERENCES users(id) ON DELETE SET NULL)");
			$this->pdo->exec("CREATE TABLE IF NOT EXISTS ticket_replies (id INT AUTO_INCREMENT PRIMARY KEY,ticket_id INT NOT NULL,user_id INT NULL,gmail_message_id INT NULL,sender_type VARCHAR(20) NOT NULL,message LONGTEXT NOT NULL,created_at DATETIME DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE SET NULL,FOREIGN KEY (gmail_message_id) REFERENCES gmail_messages(id) ON DELETE SET NULL)");
			$this->pdo->exec("CREATE TABLE IF NOT EXISTS email_ai_analysis (id INT AUTO_INCREMENT PRIMARY KEY,gmail_message_id INT NOT NULL,category VARCHAR(50) NOT NULL,is_ticket TINYINT(1) DEFAULT 0,is_feedback TINYINT(1) DEFAULT 0,summary TEXT NULL,detected_issue TEXT NULL,confidence DECIMAL(5,2) NULL,created_at DATETIME DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY (gmail_message_id) REFERENCES gmail_messages(id) ON DELETE SET NULL)");
			$this->pdo->exec("CREATE TABLE IF NOT EXISTS sync_logs (id INT AUTO_INCREMENT PRIMARY KEY,processed_emails INT DEFAULT 0,tickets_created INT DEFAULT 0,status VARCHAR(50) DEFAULT 'success',duration FLOAT DEFAULT 0,created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
			
			$count = $this->pdo->query("SELECT COUNT(*) FROM roles")->fetchColumn();
            if ($count == 0) $this->pdo->exec("INSERT INTO roles (name) VALUES('Admin'),('Manager'),('Commercial')");

            $countUsers = $this->pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            if ($countUsers == 0) $this->pdo->exec("INSERT INTO users (first_name, last_name, email, password, role_id) VALUES('Admin', 'System', 'admin@gmail.com', '0001', 1),('Sara', 'Manager', 'manager@gmail.com', '0002', 2),('Ali', 'Commercial', 'commercial@gmail.com', '0003', 3) ");
            
        } catch (PDOException $e) {die("Erreur d'initialisation : " . $e->getMessage());}
    }

    public function getConnection() {return $this->pdo;}
	
//-------------------------------------------------------------------------------------------------------------------------------------------|
// |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ ClientRT ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~|
//-------------------------------------------------------------------------------------------------------------------------------------------|
	//Getters
    public function getAllClients() {
        $stmt = $this->pdo->query("SELECT * FROM clients ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getClientById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM clients WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
	
	public function getClientByEmail($email) {
		$stmt = $this->pdo->prepare(" SELECT * FROM clients WHERE email = ? LIMIT 1");
		$stmt->execute([$email]);
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}
	
	public function countClients() {
        return $this->pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();
    }

	//Setters
    public function addClient($first_name, $last_name, $email, $phone, $address) {
        $stmt = $this->pdo->prepare("INSERT INTO clients (first_name, last_name, email, phone, address)VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$first_name, $last_name, $email, $phone, $address]);
    }
	
    public function updateClient($id, $first_name, $last_name, $email, $phone, $address) {
        $stmt = $this->pdo->prepare("UPDATE clients SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?WHERE id = ?");
        return $stmt->execute([$first_name, $last_name, $email, $phone, $address, $id]);
    }

    public function deleteClient($id) {
        $stmt = $this->pdo->prepare("DELETE FROM clients WHERE id = ?");
        return $stmt->execute([$id]);
    }
//-------------------------------------------------------------------------------------------------------------------------------------------|
// |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ AllUsers ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~|
//-------------------------------------------------------------------------------------------------------------------------------------------|
	//Getters
    public function getAllUsers() {
        $stmt = $this->pdo->query("SELECT users.*, roles.name AS role_name FROM users LEFT JOIN roles ON users.role_id = roles.id");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllRoles() {
        $stmt = $this->pdo->query("SELECT * FROM roles");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT users.*, roles.name AS role_name FROM users LEFT JOIN roles ON users.role_id = roles.id WHERE users.email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
	
	public function countUsers() {
        return $this->pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    }

    public function countUsersByRole() {
        $stmt = $this->pdo->query(" SELECT roles.name, COUNT(users.id) AS total FROM roles LEFT JOIN users ON users.role_id = roles.id GROUP BY roles.id, roles.name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
	
	public function isEmailTakenByAnotherUser($email, $id) {
		$stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
		$stmt->execute([$email, $id]);
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	public function verifyUserPassword($id, $password) {
    $stmt = $this->pdo->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) return false;

    return $user['password'] === $password;
	}
	
	//Setters
	public function addUser($first_name, $last_name, $email, $password, $role_id) {
        $stmt = $this->pdo->prepare("INSERT INTO users (first_name, last_name, email, password, role_id) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$first_name, $last_name, $email, $password, $role_id]);
    }
	
	public function updateUser($id, $first_name, $last_name, $email, $role_id) {
        $stmt = $this->pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, role_id = ? WHERE id = ?");
        return $stmt->execute([$first_name, $last_name, $email, $role_id, $id]);
    }
	
	public function deleteUser($id) {
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }
	
	public function updateUserRole($user_id, $role_id) {
        $stmt = $this->pdo->prepare("UPDATE users SET role_id = ? WHERE id = ?");
        return $stmt->execute([$role_id, $user_id]);
    }

    public function updateProfile($id, $first_name, $last_name, $email) {
        $stmt = $this->pdo->prepare(" UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?");
        return $stmt->execute([$first_name, $last_name, $email, $id]);
    }
	
	public function updatePassword($id, $new_password) {
		$stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
		return $stmt->execute([$new_password, $id]);
	}
	
//-------------------------------------------------------------------------------------------------------------------------------------------|
// |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ GmailAcc ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~|
//-------------------------------------------------------------------------------------------------------------------------------------------|
	//Getters
	public function getGmailToken() {
		$stmt = $this->pdo->query("SELECT * FROM gmail_tokens ORDER BY id DESC LIMIT 1");
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}
	
	//Setters
	public function saveGmailToken($access_token, $refresh_token, $expires_at) {
		$stmt = $this->pdo->prepare("INSERT INTO gmail_tokens (access_token, refresh_token, expires_at) VALUES (?, ?, ?)");
		return $stmt->execute([$access_token, $refresh_token, $expires_at]);
	}
	
	public function updateGmailToken($access_token, $expires_at) {
		$stmt = $this->pdo->prepare("UPDATE gmail_tokens SET access_token = ?, expires_at = ? WHERE id = (SELECT id FROM (SELECT id FROM gmail_tokens ORDER BY id DESC LIMIT 1) t)");
		return $stmt->execute([$access_token, $expires_at]);
	}
	
//-------------------------------------------------------------------------------------------------------------------------------------------|
// |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ GmailClT ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~|
//-------------------------------------------------------------------------------------------------------------------------------------------|
	//Getters
	public function getClientConversation($client_id) {
		$stmt = $this->pdo->prepare("SELECT * FROM gmail_messages WHERE client_id = ? ORDER BY created_at DESC");
		$stmt->execute([$client_id]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	
	public function getGmailMessageByGmailId($gmail_id) {
		$stmt = $this->pdo->prepare("SELECT * FROM gmail_messages WHERE gmail_id = ? LIMIT 1");
		$stmt->execute([$gmail_id]);
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}
	
	public function getRecentGmailMessages($limit = 3) {
		$stmt = $this->pdo->prepare("SELECT  id, sender_email, receiver_email, subject, direction, created_at FROM gmail_messages ORDER BY created_at DESC LIMIT ?");
		$stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	
	//Setters
	public function saveGmailMessage($gmail_id,$client_id,$sender_email,$receiver_email,$subject,$body,$direction,$created_at,$thread_id,$message_id_header) {
		$stmt = $this->pdo->prepare("INSERT IGNORE INTO gmail_messages (gmail_id, client_id, sender_email, receiver_email, subject, body, direction, created_at, thread_id, message_id_header) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
		return $stmt->execute([$gmail_id,$client_id,$sender_email, $receiver_email,$subject,$body,$direction,$created_at,$thread_id,$message_id_header]);
	}

	
//-------------------------------------------------------------------------------------------------------------------------------------------|
// |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ TicketsT ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~|
//-------------------------------------------------------------------------------------------------------------------------------------------|	
	//Getters
	public function getTicketsByClient($client_id) {
		$stmt = $this->pdo->prepare(" SELECT  t.*, u.first_name AS claimed_first_name, u.last_name AS claimed_last_name FROM tickets t LEFT JOIN users u ON t.claimed_by = u.id WHERE t.client_id = ? ORDER BY t.created_at DESC");
		$stmt->execute([$client_id]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function getTicketById($ticket_id) {
		$stmt = $this->pdo->prepare("SELECT * FROM tickets WHERE id = ?");
		$stmt->execute([$ticket_id]);
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}
	
	public function getTicketReplies($ticket_id) {
		$stmt = $this->pdo->prepare("SELECT  tr.*, u.first_name AS user_first_name, u.last_name  AS user_last_name, u.email      AS user_email, c.first_name AS client_first_name, c.last_name  AS client_last_name FROM ticket_replies tr LEFT JOIN users u   ON tr.user_id = u.id LEFT JOIN tickets t ON tr.ticket_id = t.id LEFT JOIN clients c ON t.client_id = c.id WHERE tr.ticket_id = ? ORDER BY tr.created_at ASC");
		$stmt->execute([$ticket_id]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	
	public function getEmailAiAnalysisByMessageId($gmail_message_id) {
		$stmt = $this->pdo->prepare("SELECT * FROM email_ai_analysis WHERE gmail_message_id = ? LIMIT 1");
		$stmt->execute([$gmail_message_id]);
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}
	
	public function getGmailMessageFromTicket($ticket_id) {
		$stmt = $this->pdo->prepare("SELECT gm.* FROM tickets t JOIN gmail_messages gm ON t.gmail_message_id = gm.id WHERE t.id = ? LIMIT 1");
		$stmt->execute([$ticket_id]);
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}
	
	public function getRecentTickets($limit = 3) {
    $stmt = $this->pdo->prepare("SELECT  t.id, t.title, t.priority, t.status, t.created_at, t.updated_at, t.resolution_time_minutes, c.first_name, c.last_name FROM tickets t LEFT JOIN clients c ON t.client_id = c.id ORDER BY t.created_at DESC LIMIT ?");

    $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
	
	public function getAllTickets($status = null) {
    $sql = " SELECT  t.*, c.first_name AS client_first_name, c.last_name AS client_last_name, u.first_name AS claimed_first_name, u.last_name AS claimed_last_name FROM tickets t LEFT JOIN clients c ON t.client_id = c.id LEFT JOIN users u ON t.claimed_by = u.id";
    $params = [];
    if ($status !== null && in_array($status, ['open', 'claimed', 'solved'], true)) {
        $sql .= " WHERE t.status = ? ";
        $params[] = $status;
    }
    $sql .= " ORDER BY t.created_at DESC ";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function countTicketsByStatus() {
    $stmt = $this->pdo->query("SELECT status, COUNT(*) AS total FROM tickets GROUP BY status");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $counts = ['open' => 0,'claimed' => 0,'solved' => 0];

    foreach ($rows as $row) {
        $status = strtolower($row['status']);
        if (isset($counts[$status])) {
            $counts[$status] = (int)$row['total'];
        }
    }
    return $counts;
}
	
	//Setters
	public function createTicket($client_id, $gmail_message_id, $title, $issue_type, $priority, $ai_summary, $ai_confidence) {
    $stmt = $this->pdo->prepare("INSERT INTO tickets (client_id, gmail_message_id, title, issue_type, priority, ai_summary, ai_confidence) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$client_id, $gmail_message_id, $title, $issue_type, $priority, $ai_summary, $ai_confidence]);
    return $this->pdo->lastInsertId();
	}
	
	public function saveEmailAiAnalysis($gmail_message_id, $category, $is_ticket, $is_feedback, $summary, $detected_issue, $confidence) {
    $stmt = $this->pdo->prepare("INSERT INTO email_ai_analysis (gmail_message_id, category, is_ticket, is_feedback, summary, detected_issue, confidence) VALUES (?, ?, ?, ?, ?, ?, ?)");
    return $stmt->execute([$gmail_message_id,$category,$is_ticket,$is_feedback,$summary,$detected_issue,$confidence]);
}
	
	public function claimTicket($ticket_id, $user_id) {
		$stmt = $this->pdo->prepare("UPDATE tickets SET claimed_by = ?, claimed_at = NOW(), status = 'claimed' WHERE id = ? AND claimed_by IS NULL");
		return $stmt->execute([$user_id, $ticket_id]);
	}

	public function addTicketReply($ticket_id, $user_id, $gmail_message_id, $sender_type, $message) {
		$stmt = $this->pdo->prepare("INSERT INTO ticket_replies (ticket_id, user_id, gmail_message_id, sender_type, message) VALUES (?, ?, ?, ?, ?)");
		return $stmt->execute([$ticket_id, $user_id, $gmail_message_id, $sender_type, $message]);
	}

	public function solveTicket($ticket_id, $user_id) {
		$stmt = $this->pdo->prepare("UPDATE tickets SET status = 'solved', solved_by = ?, solved_at = NOW(), resolution_time_minutes = TIMESTAMPDIFF(MINUTE, created_at, NOW()) WHERE id = ? ");
		return $stmt->execute([$user_id, $ticket_id]);
	}
	
	public function getTicketByGmailThreadId($thread_id) {
		$stmt = $this->pdo->prepare("SELECT t.* FROM tickets t JOIN gmail_messages gm ON t.gmail_message_id = gm.id WHERE gm.thread_id = ? LIMIT 1");
		$stmt->execute([$thread_id]);
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	public function ticketReplyExistsByGmailMessageId($gmail_message_id) {
		$stmt = $this->pdo->prepare("SELECT id FROM ticket_replies WHERE gmail_message_id = ? LIMIT 1");
		$stmt->execute([$gmail_message_id]);
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}
	
//-------------------------------------------------------------------------------------------------------------------------------------------|
// |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ SyncLogs ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~|
//-------------------------------------------------------------------------------------------------------------------------------------------|	
	public function addSyncLog($emails, $tickets, $duration, $status = 'Success') {
		$stmt = $this->pdo->prepare("INSERT INTO sync_logs (processed_emails, tickets_created, duration, status) VALUES (?, ?, ?, ?)");
		return $stmt->execute([$emails, $tickets, $duration, $status]);
	}

	public function getRecentSyncLogs($limit = 5) {
		$stmt = $this->pdo->prepare(" SELECT * FROM sync_logs ORDER BY created_at DESC LIMIT ?");
		$stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
}	
$db = new Database();
$pdo = $db->getConnection();
?>
