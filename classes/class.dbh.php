<?php
include_once('config.php');
include_once('classes/class.db.php');

class DBH extends DB
{
    public static function getPendingCount() {
        $qry = new parent;
        $sql="SELECT * FROM applications WHERE status = 'pending'";
        $result = $qry->query($sql);
        return $result->num_rows;
    }

    public static function getApplication($characterID) {
        $qry = new parent;
        $sql="SELECT * FROM applications WHERE characterID=".$characterID;
        $result = $qry->query($sql);
        if($result->num_rows) {
            $row = $result->fetch_assoc();
            $response = $row;
        } else {
            return False;
        }
        return $response;
    }

    public static function getAlts($characterID) {
        $response = array();
        $qry = new parent;
        $sql="SELECT alts.*, esisso.characterName as altName FROM alts INNER JOIN esisso ON alts.altID = esisso.characterID WHERE alts.characterID=".$characterID;
        $result = $qry->query($sql);
        $response = array();
        while ($row = $result->fetch_assoc()) {
            $response[] = array('id' => $row['altID'], 'name' => $row['altName']);
        }
        return $response;
    }

    public static function getAltOf($characterID) {
        $response = null;
        $qry = new parent;
        $sql="SELECT alts.*, esisso.characterName as altName FROM alts INNER JOIN esisso ON alts.characterID = esisso.characterID WHERE alts.altID=".$characterID;
        $result = $qry->query($sql);
        if($result->num_rows) {
            $response = array();
            if ($row = $result->fetch_assoc()) {
                $altof = array();
                $altof['id'] = $row['characterID']; 
                $altof['name'] = $row['altName'];
                $response[] = $altof;
            }
        }
        return $response;
    }

    public static function getRecruiters() {
        $response = array();
        $qry = new parent;
        $sql="SELECT * FROM recruiters";
        $result = $qry->query($sql);
        while ($row = $result->fetch_assoc()) {
            $response[] = $row;
        }
        return $response;
    }

    public static function getInternals() {
        $response = array();
        $qry = new parent;
        $sql="SELECT * FROM internals";
        $result = $qry->query($sql);
        while ($row = $result->fetch_assoc()) {
            $response[] = $row;
        }
        return $response;
    }


    public static function getCorpMembers($corpid) {
        $response = array();
        $qry = new parent;
        $sql="SELECT * FROM members WHERE corporationID=".$corpid;
        $result = $qry->query($sql);
        while ($row = $result->fetch_assoc()) {
            $response[] = $row;
        }
        return $response;
    }

    public static function getMembers($orderbycheck=false) {
        $response = array();
        $qry = new parent;
        $sql="SELECT members.*, esisso.ownerHash FROM members LEFT JOIN (pilots INNER JOIN esisso ON pilots.characterID = esisso.characterID) ON members.characterID = pilots.characterID";
        if ($orderbycheck) {
            $sql.=" ORDER BY lastCheck";
        } else {
            $sql.=" ORDER BY characterName";
        }
        $result = $qry->query($sql);
        while ($row = $result->fetch_assoc()) {
            $response[] = $row;
        }
        return $response;
    }

    public static function getMemberCorps() {
        $response = array();
        $qry = new parent;
        $sql="SELECT * FROM membercorps";
        $result = $qry->query($sql);
        while ($row = $result->fetch_assoc()) {
            $response[] = $row;
        }
        return $response;
    }


    public static function getNotifyRecruiters() {
        $response = array();
        $qry = new parent;
        $sql="SELECT * FROM recruiters WHERE notify=TRUE";
        $result = $qry->query($sql);
        while ($row = $result->fetch_assoc()) {
            $response[] = $row;
        }
        return $response;
    }

    public static function getConfig($key) {
        $response = null;
        $qry = new parent;
        $sql="SELECT * FROM config WHERE cfgkey='".$key."'";
        $result = $qry->query($sql);
        while ($row = $result->fetch_assoc()) {
            $response = $row['value'];
        }
        return $response;
    }

    public static function setConfig($key, $value) {
        $response = null;
        $qry = new parent;
        $sql="REPLACE INTO config (cfgkey, value) VALUES ('".$key."', '".$value."')";
        $result = $qry->query($sql);
    }

    public static function getMaxAppID() {
        $response = 0;
        $qry = new parent;
        $sql="SELECT MAX(id) as maxID FROM applications";
        $result = $qry->query($sql);
        while ($row = $result->fetch_assoc()) {
            $response = $row['maxID'];
        }
        return $response;
    }

    public static function getNotifyRecruits() {
        $response = array();
        $qry = new parent;
        $sql="SELECT notifier.*, esisso.characterName, applications.status FROM notifier 
              INNER JOIN esisso ON esisso.characterID = notifier.characterID 
              INNER JOIN applications ON applications.characterID = notifier.characterID
              WHERE notifier.failcount < 3 AND changed < (NOW() - INTERVAL 10 MINUTE) AND NOT status = 'pending'
              ORDER BY changed ASC LIMIT 5";
        $result = $qry->query($sql);
        while ($row = $result->fetch_assoc()) {
            $response[] = $row;
        }
        return $response;
    }

    public static function addNotifyRecruits($id) {
        $qry = new parent;
        $sql="REPLACE into notifier (characterID, changed) VALUES ({$id}, NOW())";
        $qry->query($sql);
    }

    public static function removeNotifyRecruits($id) {
        $qry = new parent;
        $sql="DELETE FROM notifier WHERE characterID = {$id}";
        $qry->query($sql);
    }

    public static function notifierIncreaseFailcount($id) {
        $qry = new parent;
        $sql="UPDATE notifier SET failcount = failcount + 1 WHERE characterID = {$id}";
        $qry->query($sql);
    }

}
?>
