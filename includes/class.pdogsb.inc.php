<?php
/**
 * Classe d'acces aux donnees
 *
 * PHP Version 7
 *
  * @category  PPE
 * @package   GSB
 * @author    Efrat Seneor
 * @author    Beth Sefer
 */


class PdoGsb
{
    private static $serveur = 'mysql:host=localhost';
    private static $bdd = 'dbname=gsb_frais';
    private static $user = 'root';
    private static $mdp = '';
    private static $monPdo;
    private static $monPdoGsb = null;

    /**
     * Constructeur privé, crée l'instance de PDO qui sera sollicitée
     * pour toutes les méthodes de la classe
     */
    private function __construct()
    {
        PdoGsb::$monPdo = new PDO(
            PdoGsb::$serveur . ';' . PdoGsb::$bdd,
            PdoGsb::$user,
            PdoGsb::$mdp
        );
        PdoGsb::$monPdo->query('SET CHARACTER SET utf8');
    }

    /**
     * Méthode destructeur appelée dès qu'il n'y a plus de référence sur un
     * objet donné, ou dans n'importe quel ordre pendant la séquence d'arrêt.
     */
    public function __destruct()
    {
        PdoGsb::$monPdo = null;
    }

    /**
     * Fonction statique qui crée l'unique instance de la classe
     * Appel : $instancePdoGsb = PdoGsb::getPdoGsb();
     *
     * @return l'unique objet de la classe PdoGsb
     */
    public static function getPdoGsb()
    {
        if (PdoGsb::$monPdoGsb == null) {
            PdoGsb::$monPdoGsb = new PdoGsb();
        }
        return PdoGsb::$monPdoGsb;
    }

    /**
     * Retourne les informations d'un visiteur
     *
     * @param String $login Login du visiteur
     * @param String $mdp   Mot de passe du visiteur
     *
     * @return l'id, le nom et le prénom sous la forme d'un tableau associatif
     */
    public function getInfosVisiteur($login, $mdp)
    {
        $requetePrepare = PdoGsb::$monPdo->prepare(
            'SELECT visiteur.id AS id, visiteur.nom AS nom, '
            . 'visiteur.prenom AS prenom '
            . 'FROM visiteur '
            . 'WHERE visiteur.login = :unLogin AND visiteur.mdp = :unMdp'
        );
        $requetePrepare->bindParam(':unLogin', $login, PDO::PARAM_STR);
        $requetePrepare->bindParam(':unMdp', $mdp, PDO::PARAM_STR);
        $requetePrepare->execute();
        return $requetePrepare->fetch();
    }
    
     /**
     * Retourne les informations d'un comptable
     *
     * @param String $login Login du comptable
     * @param String $mdp   Mot de passe du comptable
     *
     * @return l'id, le nom et le prénom sous la forme d'un tableau associatif
     */
    public function getInfosComptable($login, $mdp)
    {
        $requetePrepare = PdoGsb::$monPdo->prepare(
            'SELECT comptable.id AS id, comptable.nom AS nom, '
            . 'comptable.prenom AS prenom '
            . 'FROM comptable '
            . 'WHERE comptable.login = :unLogin AND comptable.mdp = :unMdp'
        );
        $requetePrepare->bindParam(':unLogin', $login, PDO::PARAM_STR);
        $requetePrepare->bindParam(':unMdp', $mdp, PDO::PARAM_STR);
        $requetePrepare->execute();
        return $requetePrepare->fetch();
    }
    

    /**
     * Retourne sous forme d'un tableau associatif toutes les lignes de frais
     * hors forfait concernées par les deux arguments.
     * La boucle foreach ne peut être utilisée ici car on procède
     * à une modification de la structure itérée - transformation du champ date-
     *
     * @param String $id ID du visiteur
     * @param String $mois       Mois sous la forme aaaamm
     *
     * @return tous les champs des lignes de frais hors forfait sous la forme
     * d'un tableau associatif
     */
    public function getLesFraisHorsForfait($id, $mois)
    {
        $requetePrepare = PdoGsb::$monPdo->prepare(
            'SELECT * FROM lignefraishorsforfait '
            . 'WHERE lignefraishorsforfait.idVisiteur = :unIdVisiteur '
            . 'AND lignefraishorsforfait.mois = :unMois'
        );
        $requetePrepare->bindParam(':unIdVisiteur', $id, PDO::PARAM_STR);
        $requetePrepare->bindParam(':unMois', $mois, PDO::PARAM_STR);
        $requetePrepare->execute();
        $lesLignes = $requetePrepare->fetchAll();
        for ($i = 0; $i < count($lesLignes); $i++) {
            $date = $lesLignes[$i]['date'];
            $lesLignes[$i]['date'] = dateAnglaisVersFrancais($date);
        }
        return $lesLignes;
    }

    /**
     * Retourne le nombre de justificatif d'un visiteur pour un mois donné
     *
     * @param String $id ID du visiteur
     * @param String $mois       Mois sous la forme aaaamm
     *
     * @return le nombre entier de justificatifs
     */
    public function getNbjustificatifs($id, $mois)
    {
        $requetePrepare = PdoGsb::$monPdo->prepare(
            'SELECT fichefrais.nbjustificatifs as nb FROM fichefrais '
            . 'WHERE fichefrais.id = :unIdVisiteur '
            . 'AND fichefrais.mois = :unMois'
        );
        $requetePrepare->bindParam(':unIdVisiteur', $id, PDO::PARAM_STR);
        $requetePrepare->bindParam(':unMois', $mois, PDO::PARAM_STR);
        $requetePrepare->execute();
        $laLigne = $requetePrepare->fetch();
        return $laLigne['nb'];
    }

    /**
     * Retourne sous forme d'un tableau associatif toutes les lignes de frais
     * au forfait concernées par les deux arguments
     *
     * @param String $id ID du visiteur
     * @param String $mois       Mois sous la forme aaaamm
     *
     * @return l'id, le libelle et la quantité sous la forme d'un tableau
     * associatif
     */
    public function getLesFraisForfait($id, $mois)
    {
        $requetePrepare = PdoGSB::$monPdo->prepare(
            'SELECT fraisforfait.id as idfrais, '
            . 'fraisforfait.libelle as libelle, '
            . 'lignefraisforfait.quantite as quantite '
            . 'FROM lignefraisforfait '
            . 'INNER JOIN fraisforfait '
            . 'ON fraisforfait.id = lignefraisforfait.idfraisforfait '
            . 'WHERE lignefraisforfait.idvisiteur = :unIdVisiteur '
            . 'AND lignefraisforfait.mois = :unMois '
            . 'ORDER BY lignefraisforfait.idfraisforfait '
        );
        $requetePrepare->bindParam(':unIdVisiteur', $id, PDO::PARAM_STR);
        $requetePrepare->bindParam(':unMois', $mois, PDO::PARAM_STR);
        $requetePrepare->execute();
        return $requetePrepare->fetchAll();
    }

    /**
     * Retourne tous les id de la table FraisForfait
     *
     * @return un tableau associatif
     */
    public function getLesIdFrais()
    {
        $requetePrepare = PdoGsb::$monPdo->prepare(
            'SELECT fraisforfait.id as idfrais '
            . 'FROM fraisforfait ORDER BY fraisforfait.id'
        );
        $requetePrepare->execute();
        return $requetePrepare->fetchAll();
    }

    /**
     * Met à jour la table ligneFraisForfait
     * Met à jour la table ligneFraisForfait pour un visiteur et
     * un mois donné en enregistrant les nouveaux montants
     *
     * @param String $id ID du visiteur
     * @param String $mois       Mois sous la forme aaaamm
     * @param Array  $lesFrais   tableau associatif de clé idFrais et
     *                           de valeur la quantité pour ce frais
     *
     * @return null
     */
    public function majFraisForfait($id, $mois, $lesFrais)
    {
        $lesCles = array_keys($lesFrais);
        foreach ($lesCles as $unIdFrais) {
            $qte = $lesFrais[$unIdFrais];
            $requetePrepare = PdoGSB::$monPdo->prepare(
                'UPDATE lignefraisforfait '
                . 'SET lignefraisforfait.quantite = :uneQte '
                . 'WHERE lignefraisforfait.idvisiteur = :unId '
                . 'AND lignefraisforfait.mois = :unMois '
                . 'AND lignefraisforfait.idfraisforfait = :idFrais'
            );
            $requetePrepare->bindParam(':uneQte', $qte, PDO::PARAM_INT);
            $requetePrepare->bindParam(':unId', $id, PDO::PARAM_STR);
            $requetePrepare->bindParam(':unMois', $mois, PDO::PARAM_STR);
            $requetePrepare->bindParam(':idFrais', $unIdFrais, PDO::PARAM_STR);
            $requetePrepare->execute();
        }
    }
   
    /**
     * Met à jour la table ligneFraisHorsForfait pour un visiteur et
     * un mois donné en enregistrant les nouveaux montants
     *
     * @param String $id         ID du visiteur
     * @param String $mois       Mois sous la forme aaaamm
     * @param String $libelle    Libelle du frais
     * @param String $date       Date du frais
     * @param String $montant    Montant du frais
     * @param String $idFrais    Id du frais hors forfait
     *
     * @return null
     */
    public function majFraisHorsForfait($id,$mois,$libelle,$date,$montant,$idFrais)
    {
           $dateFr = dateFrancaisVersAnglais($date);
           $requetePrepare = PdoGSB::$monPdo->prepare(      
                    'UPDATE lignefraishorsforfait '
                   . 'SET lignefraishorsforfait.date = :uneDateFr, '
                   . 'lignefraishorsforfait.montant = :unMontant, '  
                   . 'lignefraishorsforfait.libelle = :unLibelle '
                   . 'WHERE lignefraishorsforfait.idvisiteur = :unIdVisiteur '
                   . 'AND lignefraishorsforfait.mois = :unMois '
                   . 'AND lignefraishorsforfait.id = :unIdFrais'      
           );
           $requetePrepare->bindParam(':unIdVisiteur', $id, PDO::PARAM_STR);
           $requetePrepare->bindParam(':unMois', $mois, PDO::PARAM_STR);
           $requetePrepare->bindParam(':unLibelle', $libelle, PDO::PARAM_STR);
           $requetePrepare->bindParam(':uneDateFr', $dateFr, PDO::PARAM_STR);
           $requetePrepare->bindParam(':unMontant', $montant, PDO::PARAM_INT);
           $requetePrepare->bindParam(':unIdFrais', $idFrais, PDO::PARAM_INT);
           $requetePrepare->execute();  
        }   

    /**
     * Met à jour le nombre de justificatifs de la table ficheFrais
     * pour le mois et le visiteur concerné
     *
     * @param String  $id    ID du visiteur
     * @param String  $mois            Mois sous la forme aaaamm
     * @param Integer $nbJustificatifs Nombre de justificatifs
     *
     * @return null
     */
    public function majNbJustificatifs($id, $mois, $nbJustificatifs)
    {
        $requetePrepare = PdoGB::$monPdo->prepare(
            'UPDATE fichefrais '
            . 'SET nbjustificatifs = :unNbJustificatifs '
            . 'WHERE fichefrais.id = :unIdVisiteur '
            . 'AND fichefrais.mois = :unMois'
        );
        $requetePrepare->bindParam(
            ':unNbJustificatifs',
            $nbJustificatifs,
            PDO::PARAM_INT
        );
        $requetePrepare->bindParam(':unIdVisiteur', $id, PDO::PARAM_STR);
        $requetePrepare->bindParam(':unMois', $mois, PDO::PARAM_STR);
        $requetePrepare->execute();
    }

    /**
     * Teste si un visiteur possède une fiche de frais pour le mois passé en argument
     *
     * @param String $id ID du visiteur
     * @param String $mois       Mois sous la forme aaaamm
     *
     * @return vrai ou faux
     */
    public function estPremierFraisMois($id, $mois)
    {
        $boolReturn = false;
        $requetePrepare = PdoGsb::$monPdo->prepare(
            'SELECT fichefrais.mois FROM fichefrais '
            . 'WHERE fichefrais.mois = :unMois '
            . 'AND fichefrais.id = :unIdVisiteur'
        );
        $requetePrepare->bindParam(':unMois', $mois, PDO::PARAM_STR);
        $requetePrepare->bindParam(':unIdVisiteur', $id, PDO::PARAM_STR);
        $requetePrepare->execute();
        if (!$requetePrepare->fetch()) {
            $boolReturn = true;
        }
        return $boolReturn;
    }

    /**
     * Retourne le dernier mois en cours d'un visiteur
     *
     * @param String $id ID du visiteur
     *
     * @return le mois sous la forme aaaamm
     */
    public function dernierMoisSaisi($id)
    {
        $requetePrepare = PdoGsb::$monPdo->prepare(
            'SELECT MAX(mois) as dernierMois '
            . 'FROM fichefrais '
            . 'WHERE fichefrais.id = :unIdVisiteur'
        );
        $requetePrepare->bindParam(':unIdVisiteur', $id, PDO::PARAM_STR);
        $requetePrepare->execute();
        $laLigne = $requetePrepare->fetch();
        $dernierMois = $laLigne['dernierMois'];
        return $dernierMois;
    }


    /**
     * Crée une nouvelle fiche de frais et les lignes de frais au forfait
     * pour un visiteur et un mois donnés
     *
     * Récupère le dernier mois en cours de traitement, met à 'CL' son champs
     * idEtat, crée une nouvelle fiche de frais avec un idEtat à 'CR' et crée
     * les lignes de frais forfait de quantités nulles
     *
     * @param String $id ID du visiteur
     * @param String $mois       Mois sous la forme aaaamm
     *
     * @return null
     */
    public function creeNouvellesLignesFrais($id, $mois)
    {
        $dernierMois = $this->dernierMoisSaisi($id);
        $laDerniereFiche = $this->getLesInfosFicheFrais($id, $dernierMois);
        if ($laDerniereFiche['idetat'] == 'CR') {
            $this->majEtatFicheFrais($id, $dernierMois, 'CL');
        }

	// CR = en cours
	// CL = cloturé
	// maj = mise à jour

        $requetePrepare = PdoGsb::$monPdo->prepare(
            'INSERT INTO fichefrais (id,mois,nbjustificatifs,'
            . 'montantvalide,datemodif,idetat) '
            . "VALUES (:unIdVisiteur,:unMois,0,0,now(),'CR')"
        );
        $requetePrepare->bindParam(':unIdVisiteur', $id, PDO::PARAM_STR);
        $requetePrepare->bindParam(':unMois', $mois, PDO::PARAM_STR);
        $requetePrepare->execute();
        
        $lesIdFrais = $this->getLesIdFrais();
        foreach ($lesIdFrais as $unIdFrais) {
            $requetePrepare = PdoGsb::$monPdo->prepare(
                'INSERT INTO lignefraisforfait (idvisiteur,mois,'
                . 'idfraisforfait,quantite) '
                . 'VALUES(:unIdVisiteur, :unMois, :idFrais, 0)'
            );
            $requetePrepare->bindParam(':unIdVisiteur', $id, PDO::PARAM_STR);
            $requetePrepare->bindParam(':unMois', $mois, PDO::PARAM_STR);
            $requetePrepare->bindParam(':idFrais',$unIdFrais['idfrais'],PDO::PARAM_STR
            );
          $requetePrepare->execute();
      
        }
    }

    /**
     * Crée un nouveau frais hors forfait pour un visiteur un mois donné
     * à partir des informations fournies en paramètre
     *
     * @param String $id ID du visiteur
     * @param String $mois       Mois sous la forme aaaamm
     * @param String $libelle    Libellé du frais
     * @param String $date       Date du frais au format français jj//mm/aaaa
     * @param Float  $montant    Montant du frais
     *
     * @return null
     */
    public function creeNouveauFraisHorsForfait($id,$mois,$libelle,$date,$montant) {
        $dateFr = dateFrancaisVersAnglais($date);
        $requetePrepare = PdoGSB::$monPdo->prepare(
            'INSERT INTO lignefraishorsforfait '
            . 'VALUES (null, :unIdVisiteur,:unMois, :unLibelle, :uneDateFr,'
            . ':unMontant) '
        );
        $requetePrepare->bindParam(':unIdVisiteur', $id, PDO::PARAM_STR);
        $requetePrepare->bindParam(':unMois', $mois, PDO::PARAM_STR);
        $requetePrepare->bindParam(':unLibelle', $libelle, PDO::PARAM_STR);
        $requetePrepare->bindParam(':uneDateFr', $dateFr, PDO::PARAM_STR);
        $requetePrepare->bindParam(':unMontant', $montant, PDO::PARAM_INT);
        $requetePrepare->execute();
    }

    /**
     * Supprime le frais hors forfait dont l'id est passé en argument
     *
     * @param String $idFrais ID du frais
     *
     * @return null
     */
    public function supprimerFraisHorsForfait($idFrais)
    {
        $requetePrepare = PdoGSB::$monPdo->prepare(
            'DELETE FROM lignefraishorsforfait '
            . 'WHERE lignefraishorsforfait.id = :unIdFrais'
        );
        $requetePrepare->bindParam(':unIdFrais', $idFrais, PDO::PARAM_STR);
        $requetePrepare->execute();
    }

    /**
     * Retourne les mois pour lesquel un visiteur a une fiche de frais
     * @param String $id ID du visiteur
     *
     * @return un tableau associatif de clé un mois -aaaamm- et de valeurs
     *         l'année et le mois correspondant
     */
    public function getLesMoisDisponibles($id)
    {
        $requetePrepare = PdoGSB::$monPdo->prepare(
            'SELECT fichefrais.mois AS mois FROM fichefrais '
            . 'WHERE fichefrais.id = :unIdVisiteur '
            . 'ORDER BY fichefrais.mois desc'
        );
        $requetePrepare->bindParam(':unIdVisiteur', $id, PDO::PARAM_STR);
        $requetePrepare->execute();
        $lesMois = array();
        while ($laLigne = $requetePrepare->fetch()) {
            $mois = $laLigne['mois'];
            $numAnnee = substr($mois, 0, 4);
            $numMois = substr($mois, 4, 2);
            $lesMois[] = array(
                'mois' => $mois,
                'numAnnee' => $numAnnee,
                'numMois' => $numMois
            );
        }
        return $lesMois;
    }
    
     /**
     * Retourne tous les visiteurs (les id, noms, prenom) de la table visiteur
     *
     * @return un tableau associatif des visiteurs
     */
        public function getLesVisiteurs()
    {
        $requetePrepare = PdoGSB::$monPdo->prepare(
            'SELECT visiteur.id, visiteur.nom, visiteur.prenom '
            . 'FROM visiteur'   
        );
        $requetePrepare->execute();
        return $requetePrepare->fetchAll();
//        var_dump ($requetePrepare);    
    }


    /**
     * Retourne les informations d'une fiche de frais d'un visiteur pour un
     * mois donné
     *
     * @param String $id ID du visiteur
     * @param String $mois       Mois sous la forme aaaamm
     *
     * @return un tableau avec des champs de jointure entre une fiche de frais
     *         et la ligne d'état
     */
    public function getLesInfosFicheFrais($idVisiteur, $mois)
    {
        $requetePrepare = PdoGSB::$monPdo->prepare(
            'SELECT fichefrais.idetat as idetat, '
            . 'fichefrais.datemodif as datemodif,'
            . 'fichefrais.nbjustificatifs as nbjustificatifs, '
            . 'fichefrais.montantvalide as montantvalide, '
            . 'etat.libelle as libetat '
            . 'FROM fichefrais '
            . 'INNER JOIN etat ON fichefrais.idetat = etat.id '
            . 'WHERE fichefrais.id = :unIdVisiteur '
            . 'AND fichefrais.mois = :unMois'
        );
        $requetePrepare->bindParam(':unIdVisiteur', $idVisiteur, PDO::PARAM_STR);//permet de definir que le mdp et le login envoyés en paramètre correspondent à ceux récupérés de la bdd par la requete sql.
        $requetePrepare->bindParam(':unMois', $mois, PDO::PARAM_STR);
        $requetePrepare->execute();
        $laLigne = $requetePrepare->fetch();
        return $laLigne;
    }
    
    /**
     * Modifie l'état et la date de modification d'une fiche de frais.
     * Modifie le champ idEtat et met la date de modif à aujourd'hui.
     *
     * @param String $id ID du visiteur
     * @param String $mois       Mois sous la forme aaaamm
     * @param String $etat       Nouvel état de la fiche de frais
     *
     * @return null
     */
    public function majEtatFicheFrais($id, $mois, $etat)
    {
        $requetePrepare = PdoGSB::$monPdo->prepare(
            'UPDATE fichefrais '
            . 'SET idetat = :unEtat, datemodif = now() '
            . 'WHERE fichefrais.id = :unIdVisiteur '
            . 'AND fichefrais.mois = :unMois '
        );
        $requetePrepare->bindParam(':unEtat', $etat, PDO::PARAM_STR);
        $requetePrepare->bindParam(':unIdVisiteur', $id, PDO::PARAM_STR);
        $requetePrepare->bindParam(':unMois', $mois, PDO::PARAM_STR);
        $requetePrepare->execute();
    }
    
    /**
     * Modifie le libelle du frais à reporter (REFUSE: nomdufrais)
     *
     * @param String $idFrais    ID du frais hors forfait à reporter
     *
     * @return null
     */
    public function majLibelle($idFrais){
        $requetePrepare = PdoGSB::$monPdo->prepare( 
                'UPDATE lignefraishorsforfait '
                .' SET libelle = CONCAT("Refusé: ",libelle) '
                .' WHERE lignefraishorsforfait.id =:unId '
        );  
        $requetePrepare->bindParam(':unId', $idFrais, PDO::PARAM_STR);
        $requetePrepare->execute();
    }
    
    /**
     * Modifie le mois du frais à reporter (passer du mois actuel au mois prochain)
     *
     * @param String $idFrais       ID du frais hors forfait à reporter
     * @param String $moisSuivant   mois qui suit le mois du frais
     *
     * @return null
     */
    public function reporterFHF($idFrais, $moisSuivant){
        $requetePrepare = PdoGSB::$monPdo->prepare(
                'UPDATE lignefraishorsforfait '
                .' SET mois = :unMois '
                .' WHERE lignefraishorsforfait.id = :unId '
        );
        $requetePrepare->bindParam(':unId', $idFrais, PDO::PARAM_STR);
        $requetePrepare->bindParam(':unMois', $moisSuivant, PDO::PARAM_STR);
        $requetePrepare->execute();
    }

    /**
     * Calcule la somme des frais forfait pour un visiteur et un mois donné 
     * (produit des quantités par le montant des frais forfait)
     *
     * @param String $idVisiteur      ID du visiteur
     * @param String $leMois          Mois du frais
     *
     * @return un tableau avec le montant des frais forfait
     */
    public function montantFF($idVisiteur, $leMois){
        $requetePrepare = PdoGSB::$monPdo->prepare(
                'SELECT SUM(fraisforfait.montant*lignefraisforfait.quantite) '
                .' FROM fraisforfait JOIN lignefraisforfait ON(fraisforfait.id=lignefraisforfait.idfraisforfait) '
                .' WHERE lignefraisforfait.idvisiteur = :unId '
                .' AND lignefraisforfait.mois = :unMois '
                .' AND fraisforfait.id IN("ETP","NUI","REP")' 
                .' AND lignefraisforfait.idfraisforfait IN("ETP","NUI","REP")'
        );
        $requetePrepare->bindParam(':unId', $idVisiteur, PDO::PARAM_STR);
        $requetePrepare->bindParam(':unMois', $leMois, PDO::PARAM_STR);
        $requetePrepare->execute();
        return $requetePrepare->fetchAll();
    }
    
    /**
     * Calcule la somme des frais hors forfait pour un visiteur et un mois donné 
     * (somme de tous les montants des frais hors forfait)
     *
     * @param String $idVisiteur      ID du visiteur
     * @param String $leMois          Mois du frais
     *
     * @return un tableau avec la somme des frais hors forfait
     */
    public function montantFHF($idVisiteur, $leMois){
        $requetePrepare = PdoGSB::$monPdo->prepare(
                'SELECT SUM(lignefraishorsforfait.montant)'
                .' FROM lignefraishorsforfait '
                .' WHERE lignefraishorsforfait.idvisiteur = :unId '
                .' AND lignefraishorsforfait.mois = :unMois '
        );
        $requetePrepare->bindParam(':unId', $idVisiteur, PDO::PARAM_STR);
        $requetePrepare->bindParam(':unMois', $leMois, PDO::PARAM_STR);
        $requetePrepare->execute();
        return $requetePrepare->fetchAll();
    }
    
    /**
     * Modifie le champ montantValide de fichefrais, ajoute le montant total 
     * des frais et le nombre de justificatifs pour le mois et le visiteur donne
     *
     * @param String $idVisiteur        ID du visiteur
     * @param String $leMois            Mois sous la forme aaaamm
     * @param String $total             montant total des frais pour ce mois
     * @param String $nbJustificatifs   nombre total de justificatifs
     *
     * @return null
     */
    public function montantValide($idVisiteur, $leMois, $total, $nbJustificatifs){
        $requetePrepare = PdoGSB::$monPdo->prepare(
                'UPDATE fichefrais '
                .' SET montantvalide = :total, nbjustificatifs = :nbJstf '
                .' WHERE fichefrais.id = :unId '
                .' AND fichefrais.mois = :unMois '
        );
        $requetePrepare->bindParam(':unId', $idVisiteur, PDO::PARAM_STR);
        $requetePrepare->bindParam(':unMois', $leMois, PDO::PARAM_STR);
        $requetePrepare->bindParam(':nbJstf', $nbJustificatifs, PDO::PARAM_INT);
        $requetePrepare->bindParam(':total', $total, PDO::PARAM_STR);
        $requetePrepare->execute();
    }
    
    /**
     * Retourne le montant du km pour le vehicule d'un visiteur donné
     *
     * @param String $id              ID du visiteur
     *
     * @return un tableau avec le montant du km pour ce vehicule
     */
    public function getMontantVehicule($id)
     {
        $requetePrepare = PdoGSB::$monPdo->prepare(
        'SELECT vehicule.montantKm '
        . 'FROM vehicule '
        . 'INNER JOIN visiteur '
        . 'ON vehicule.id = visiteur.idVehicule '
        . 'WHERE visiteur.id = :unIdVisiteur '
        );
        $requetePrepare->bindParam(':unIdVisiteur', $id, PDO::PARAM_STR);
        $requetePrepare->execute();
        return $requetePrepare->fetchAll();
        }
        
    /**
     * Retourne le nombre de km parcourus par un visiteur pour un mois donné
     *
     * @param String $id              ID du visiteur
     * @param String $mois            Mois du frais
     *
     * @return un tableau avec le nombre de km parcourus
     */
        public function getQteKm($id, $mois)
     {            
        $requetePrepare = PdoGSB::$monPdo->prepare(
        'SELECT lignefraisforfait.quantite '
        . 'FROM lignefraisforfait '
        . 'WHERE lignefraisforfait.idvisiteur = :unVisiteur '
        . 'AND lignefraisforfait.mois = :unMois '
        . 'AND lignefraisforfait.idfraisforfait = "km"'
        );
        $requetePrepare->bindParam(':unVisiteur', $id, PDO::PARAM_STR);
        $requetePrepare->bindParam(':unMois', $mois, PDO::PARAM_STR);
        $requetePrepare->execute();
        return $requetePrepare->fetchAll(); 
   }
 
    /**
     * Retourne les visiteurs qui ont une fiche de frais validée (ce sont les fiches deja validees a rembourser)
     *
     * @return un tableau associatif des visiteurs
     */
    public function getLesVisiteursVA(){
        $requetePrepare = PdoGSB::$monPdo->prepare(
            'SELECT visiteur.id, visiteur.nom, visiteur.prenom '
            . 'FROM visiteur JOIN fichefrais ON (visiteur.id=fichefrais.id) ' 
            . 'WHERE fichefrais.idetat = "VA" '
        );
        $requetePrepare->execute();
        return $requetePrepare->fetchAll();    
    }
    
    /**
     * Retourne les mois pour lesquels il y a au moins une fiche de frais validée (ce sont les fiches deja validees a rembourser)
     *
     * @return un tableau associatif des mois
     */
    public function getLesMoisVA() {
    $requetePrepare = PdoGSB::$monPdo->prepare(
            'SELECT DISTINCT fichefrais.mois '
            . 'FROM fichefrais ' 
            . 'WHERE fichefrais.idetat = "VA" '
        );
        $requetePrepare->execute();
        $lesMois = array ();
        while ($laLigne=$requetePrepare->fetch()) {
        $mois = $laLigne['mois'];
        $numAnnee = substr($mois, 0, 4);
        $numMois = substr($mois, 4, 2);

        if (strlen($numMois) == 1) { //verifie le nombre de caractere dans le mois
                $numMois = '0' . $numMois;
            }
        $lesMois[] = array(
                    'mois' => $numAnnee.$numMois,
                    'numAnnee' => $numAnnee,
                    'numMois' => $numMois
                );
     }
        return $lesMois; 
     }
     
     
     
  }

  /**
     * if($requetePrepare->execute()){
     *      echo 'Succes';
     *  }
     *  else {
     *      echo 'Echec';
     *  }
     * 
     */
