<?php
include_once('config.php');

class FITTING
{
    protected $fitting = array();
    protected $shipID = null;
    protected $highs = array();
    protected $meds = array();
    protected $lows = array();
    protected $rigs = array();
    protected $subsys = array();
    protected $drones = array();
    protected $charges = array();
    protected $error = false;
    protected $message = '';
    private $typenames = array();

    public function __construct($fit = null) {
      if ($fit != null) {
        $temp = array();
        $tempmods = array();
        foreach(explode(":", $fit) as $part){
            $p = explode(";",$part);
            if (sizeof($p) > 1) {
                for ($i = 1; $i <= (int)$p[1]; $i++) {
                    $tempmods[] = (int)$p[0];
                }
            } else {
                $tempmods[] = (int)$p[0];
            }
        }
        $sql='select invTypes.typeid,typename,COALESCE(effectid,categoryID) effectid from invTypes left join dgmTypeEffects on (dgmTypeEffects.typeid=invTypes.typeid and effectid in (11,12,13,2663,3772)), invGroups where invTypes.typeid= ? and invTypes.groupid=invGroups.groupid';
        $qry = DB::getConnection();
        $stmt = $qry->prepare($sql);
        $stmt->bind_param("i", $typeid);
        $typeslot = array();
        foreach(array_unique($tempmods) as $_mod) {
            $typeid = $_mod;
            $stmt->execute();
            if ($res = $stmt->get_result()) {
                $row = $res->fetch_assoc();
                $this->typenames[$row['typeid']]=htmlentities($row['typename'], ENT_QUOTES);
                $typeslot[$row['typeid']]=$row['effectid'];
            }
        }
        foreach ($tempmods as $mod) {
            switch ($typeslot[$mod]) {
                case 6:
                    $this->shipID = $mod;
                    break;
                case 11:
                    $this->lows[] = $mod;
                    break;
                case 13:
                    $this->meds[] = $mod;
                    break;
                case 12:
                    $this->highs[] = $mod;
                    break;
                case 2663:
                    $this->rigs[] = $mod;
                    break;
                case 3772:
                    $this->subsys[] = $mod;
                    break;
                case 18:
                    $this->drones[] = $mod;
                    break;
                case 8:
                    $this->charges[] = $mod;
                    break;
            }
        }
        if ($this->shipID == null) {
            $this->error = true;
            $this->message = "Fitting could not be parsed";
        }
        $this->fitting['ship'] = $this->shipID;
        $this->fitting['lows'] = $this->lows;
        $this->fitting['meds'] = $this->meds;
        $this->fitting['highs'] = $this->highs;
        $this->fitting['rigs'] = $this->rigs;
        $this->fitting['subsys'] = $this->subsys;
        $this->fitting['drones'] = $this->drones;
        $this->fitting['charges'] = $this->charges;
      }
    }

    public static function getModGroups($fitting=null) {
        $gids = array();
        $f['ab'] = 0;
        $f['mwd'] = 0;
        $f['scram'] = 0;
        $f['disrupt'] = 0;
        $f['dis_field'] = 0;
        $f['web'] = 0;
        $f['grap'] = 0;
        $f['td'] = 0;
        $f['damp'] = 0;
        $f['paint'] = 0;
        $f['y_jam'] = 0;
        $f['r_jam'] = 0;
        $f['b_jam'] = 0;
        $f['g_jam'] = 0;
        $f['m_jam'] = 0;
        if ($fitting) {
            $qry = DB::getConnection();
            $mods = self::flatten($fitting);
            $sql="SELECT typeID, marketGroupID FROM invTypes WHERE typeID=".implode(" OR typeID=", $mods);
            $result = $qry->query($sql);
            if($result->num_rows) {
                while($row = $result->fetch_row()) {
                    $gid[$row[0]] = $row[1];
                }
                foreach ($mods as $mod) {
                    if (isset($gid[$mod])) {
                        switch($gid[$mod]) {
                            case 542:
                                $f['ab'] += 1;
                                break;
                            case 131:
                                $f['mwd'] += 1;
                                break;
                            case 1936:
                                $f['scram'] += 1;
                                break;
                            case 1935:
                                $f['disrupt'] += 1;
                                break;
                            case 1085:
                                $f['dis_field'] += 1;
                                break;
                            case 683:
                                $f['web'] += 1;
                                break;
                            case 2154:
                                $f['grap'] += 1;
                                break;
                            case 680:
                                $f['td'] += 1;
                                break;
                            case 679:
                                $f['damp'] += 1;
                                break;
                            case 757:
                                $f['paint'] += 1;
                                break;
                            case 718:
                                $f['y_jam'] += 1;
                                break;
                            case 716:
                                $f['r_jam'] += 1;
                                break;
                            case 717:
                                $f['b_yam'] += 1;
                                break;
                            case 715:
                                $f['g_jam'] += 1;
                                break;
                            case 719:
                                $f['m_jam'] += 1;
                                break;
                        }
                    }
                }
            }
        }
        return $f;
    }

    public function getNames() {
        return $this->typenames;
    }

    private static function flatten(array $array) {
        $return = array();
        array_walk_recursive($array, function($a) use (&$return) { $return[] = $a; });
        return $return;
    }

    public function getFitArray() {
        return $this->fitting;
    }

    public function getShipTypeID() {
        return $this->shipID;
    }

    public function getError() {
        return $this->error;
    }

    public function getMessage() {
        return $this->message;
    }

}
?>
