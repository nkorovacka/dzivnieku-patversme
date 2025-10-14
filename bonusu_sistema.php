<?php
require_once 'db_conn.php';

class BonusuSistema {
    private $conn;
    
    // Bonusu konstantes
    const BONUSS_REGISTRACIJA = 100;
    const BONUSS_DIENAS = 10;
    const BONUSS_PIEVIENOT_DZIVNIEKU = 50;
    const BONUSS_PIEVIENOT_FAVORITOS = 5;
    const BONUSS_PROFILS_AIZPILDITS = 25;
    
    // Līmeņu sistēma
    const PIEREDZE_UZ_LIMENI = 100;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Inicializē bonusu sistēmu jaunam lietotājam
     */
    public function inicializetLietotajuBonusu($lietotajs_id) {
        $stmt = $this->conn->prepare("INSERT INTO lietotaju_bonusi (lietotajs_id, esosie_punkti) VALUES (?, ?)");
        $sakuma_punkti = self::BONUSS_REGISTRACIJA;
        $stmt->bind_param("ii", $lietotajs_id, $sakuma_punkti);
        
        if ($stmt->execute()) {
            $this->pievienotTransakciju($lietotajs_id, $sakuma_punkti, 'registracija', 'Bonuss par reģistrāciju');
            return true;
        }
        return false;
    }
    
    /**
     * Pievienot bonusu punktus
     */
    public function pievienotPunktus($lietotajs_id, $punkti, $darbibas_veids, $apraksts = '') {
        $stmt = $this->conn->prepare("
            UPDATE lietotaju_bonusi 
            SET esosie_punkti = esosie_punkti + ?,
                kopeja_nopelnita_summa = kopeja_nopelnita_summa + ?,
                pieredze = pieredze + ?
            WHERE lietotajs_id = ?
        ");
        $stmt->bind_param("iiii", $punkti, $punkti, $punkti, $lietotajs_id);
        
        if ($stmt->execute()) {
            $this->pievienotTransakciju($lietotajs_id, $punkti, $darbibas_veids, $apraksts);
            $this->parbauditLimeni($lietotajs_id);
            return true;
        }
        return false;
    }
    
    /**
     * Noņemt bonusu punktus
     */
    public function noemtPunktus($lietotajs_id, $punkti, $darbibas_veids, $apraksts = '') {
        $esosie = $this->iegutLietotajaPunktus($lietotajs_id);
        if ($esosie < $punkti) {
            return false;
        }
        
        $stmt = $this->conn->prepare("
            UPDATE lietotaju_bonusi 
            SET esosie_punkti = esosie_punkti - ?
            WHERE lietotajs_id = ?
        ");
        $stmt->bind_param("ii", $punkti, $lietotajs_id);
        
        if ($stmt->execute()) {
            $negativie_punkti = -$punkti;
            $this->pievienotTransakciju($lietotajs_id, $negativie_punkti, $darbibas_veids, $apraksts);
            return true;
        }
        return false;
    }
    
    /**
     * Saņemt dienas bonusu
     */
    public function sanemt_dienas_bonusu($lietotajs_id) {
        $stmt = $this->conn->prepare("
            SELECT pedeja_dienas_balva 
            FROM lietotaju_bonusi 
            WHERE lietotajs_id = ?
        ");
        $stmt->bind_param("i", $lietotajs_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        $sodien = date('Y-m-d');
        
        if ($row['pedeja_dienas_balva'] != $sodien) {
            $stmt = $this->conn->prepare("
                UPDATE lietotaju_bonusi 
                SET pedeja_dienas_balva = ? 
                WHERE lietotajs_id = ?
            ");
            $stmt->bind_param("si", $sodien, $lietotajs_id);
            $stmt->execute();
            
            $this->pievienotPunktus($lietotajs_id, self::BONUSS_DIENAS, 'dienas_bonuss', 'Ikdienas bonuss');
            return true;
        }
        return false;
    }
    
    /**
     * Pārbaudīt un paaugstināt līmeni
     */
    private function parbauditLimeni($lietotajs_id) {
        $stats = $this->iegutLietotajaStatistiku($lietotajs_id);
        $jauns_limenis = floor($stats['pieredze'] / self::PIEREDZE_UZ_LIMENI) + 1;
        
        if ($jauns_limenis > $stats['limenis']) {
            $stmt = $this->conn->prepare("
                UPDATE lietotaju_bonusi 
                SET limenis = ? 
                WHERE lietotajs_id = ?
            ");
            $stmt->bind_param("ii", $jauns_limenis, $lietotajs_id);
            $stmt->execute();
            
            // Bonuss par līmeņa paaugstināšanu
            $bonuss = $jauns_limenis * 20;
            $this->pievienotPunktus($lietotajs_id, $bonuss, 'limena_bonuss', "Līmeņa paaugstinājums līdz {$jauns_limenis}!");
        }
    }
    
    /**
     * Iegūt lietotāja punktus
     */
    public function iegutLietotajaPunktus($lietotajs_id) {
        $stmt = $this->conn->prepare("SELECT esosie_punkti FROM lietotaju_bonusi WHERE lietotajs_id = ?");
        $stmt->bind_param("i", $lietotajs_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['esosie_punkti'] ?? 0;
    }
    
    /**
     * Iegūt lietotāja statistiku
     */
    public function iegutLietotajaStatistiku($lietotajs_id) {
        $stmt = $this->conn->prepare("
            SELECT esosie_punkti, kopeja_nopelnita_summa, pedeja_dienas_balva, limenis, pieredze
            FROM lietotaju_bonusi 
            WHERE lietotajs_id = ?
        ");
        $stmt->bind_param("i", $lietotajs_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    /**
     * Iegūt transakciju vēsturi
     */
    public function iegutTransakcijuVesturi($lietotajs_id, $limits = 10) {
        $stmt = $this->conn->prepare("
            SELECT * FROM bonusu_transakcijas 
            WHERE lietotajs_id = ? 
            ORDER BY izveidots DESC 
            LIMIT ?
        ");
        $stmt->bind_param("ii", $lietotajs_id, $limits);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Iegūt visas privileģijas
     */
    public function iegutVisasPrivilegijas() {
        $result = $this->conn->query("SELECT * FROM privilegijas WHERE aktiva = TRUE ORDER BY cena ASC");
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Nopirkt privileģiju
     */
    public function nopirktPrivilegiju($lietotajs_id, $privilegija_id) {
        // Pārbaudām vai lietotājam jau nav šī privileģija
        $stmt = $this->conn->prepare("SELECT id FROM lietotaju_privilegijas WHERE lietotajs_id = ? AND privilegija_id = ?");
        $stmt->bind_param("ii", $lietotajs_id, $privilegija_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            return ['success' => false, 'message' => 'Jums jau ir šī privilēģija!'];
        }
        
        // Iegūstam privilēģijas cenu
        $stmt = $this->conn->prepare("SELECT cena, nosaukums FROM privilegijas WHERE id = ?");
        $stmt->bind_param("i", $privilegija_id);
        $stmt->execute();
        $priv = $stmt->get_result()->fetch_assoc();
        
        if (!$priv) {
            return ['success' => false, 'message' => 'Privilēģija nav atrasta!'];
        }
        
        // Pārbaudām vai pietiek punktu
        $punkti = $this->iegutLietotajaPunktus($lietotajs_id);
        if ($punkti < $priv['cena']) {
            return ['success' => false, 'message' => 'Nepietiek punktu!'];
        }
        
        // Noņemam punktus
        if (!$this->noemtPunktus($lietotajs_id, $priv['cena'], 'privilegija', "Nopirkta: {$priv['nosaukums']}")) {
            return ['success' => false, 'message' => 'Kļūda noņemot punktus!'];
        }
        
        // Pievienojam privilēģiju
        $stmt = $this->conn->prepare("INSERT INTO lietotaju_privilegijas (lietotajs_id, privilegija_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $lietotajs_id, $privilegija_id);
        $stmt->execute();
        
        return ['success' => true, 'message' => 'Privilēģija veiksmīgi iegādāta!'];
    }
    
    /**
     * Iegūt lietotāja privilēģijas
     */
    public function iegutLietotajaPrivilegijas($lietotajs_id) {
        $stmt = $this->conn->prepare("
            SELECT p.*, lp.iegutes 
            FROM lietotaju_privilegijas lp
            JOIN privilegijas p ON lp.privilegija_id = p.id
            WHERE lp.lietotajs_id = ?
            ORDER BY lp.iegutes DESC
        ");
        $stmt->bind_param("i", $lietotajs_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Pievienot transakciju vēsturē
     */
    private function pievienotTransakciju($lietotajs_id, $punkti, $darbibas_veids, $apraksts) {
        $stmt = $this->conn->prepare("
            INSERT INTO bonusu_transakcijas (lietotajs_id, punkti, darbibas_veids, apraksts) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("iiss", $lietotajs_id, $punkti, $darbibas_veids, $apraksts);
        $stmt->execute();
    }
}
?>