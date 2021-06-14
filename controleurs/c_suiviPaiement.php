<?php
/**
 * Gestion du suivi du paiement
 *
 * PHP Version 7
 *
 * @category  PPE
 * @package   GSB
 * @author    Efrat Seneor
 * @author    Beth Sefer
 */

$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
$id = $_SESSION['id'];
$mois=getMois(date('d/m/Y'));

switch ($action) {
case 'selectionnerVM':
    $visiteurs = $pdo->getLesVisiteursVA();
    if(empty($visiteurs)){
    ?>
        <div class="alert alert-info" role="alert">
            <p>Oupss... Aucune fiche à rembourser. Commencez par valider les fiches de frais. <a href="index.php"> Cliquez ici</a>
                pour revenir à la page d'accueil.</p>
        </div>
    <?php
    }
    else{
    $clesVisiteur = array_keys($visiteurs);
    $visiteursASelectionner = $clesVisiteur[0];
    $idVisiteur = filter_input(INPUT_POST, 'lstVisiteurs', FILTER_SANITIZE_STRING);
    $mois = getMois(date('d/m/Y')); 
    $lesMois = $pdo->getLesMoisVA();
    $clesMois = array_keys($lesMois);
    $moisASelectionner = $clesMois[0];
    include 'vues/v_listeVM2.php';
    }
    break;

case 'afficherFrais':     
    $idVisiteur = filter_input(INPUT_POST, 'lstVisiteurs', FILTER_SANITIZE_STRING);
    $lesVisiteurs=$pdo->getLesVisiteursVA();
    $visiteurASelectionner=$idVisiteur; 
    $lesMois = $pdo->getLesMoisDisponibles($idVisiteur);
    $leMois = filter_input(INPUT_POST, 'lstMois', FILTER_SANITIZE_STRING);
    $moisASelectionner = $leMois;
    $lesFraisHorsForfait = $pdo->getLesFraisHorsForfait($idVisiteur, $leMois);
    $lesFraisForfait = $pdo->getLesFraisForfait($idVisiteur, $leMois);
    $lesInfosFicheFrais = $pdo->getLesInfosFicheFrais($idVisiteur, $leMois);
    $numAnnee = substr($leMois, 0, 4);
    $numMois = substr($leMois, 4, 2);
    $libEtat = $lesInfosFicheFrais['libetat'];
    $montantValide = $lesInfosFicheFrais['montantvalide'];
    $nbJustificatifs = $lesInfosFicheFrais['nbjustificatifs'];
    $dateModif = dateAnglaisVersFrancais($lesInfosFicheFrais['datemodif']);
    include 'vues/v_suiviFrais.php';
break;

case 'miseEnPaiement':
    $idVisiteur = filter_input(INPUT_POST, 'lstVisiteurs', FILTER_SANITIZE_STRING);
    $lesVisiteurs=$pdo->getLesVisiteurs();
    $visiteurASelectionner=$idVisiteur;  
    $leMois = filter_input(INPUT_POST, 'lstMois', FILTER_SANITIZE_STRING);//on recupere ce qui a ete selectionné ds la liste deroulante de nummois(qui se trouve dans v_listemois).
    $lesMois = $pdo->getLesMoisDisponibles($idVisiteur);
    $moisASelectionner = $leMois;
    $etat="RB";
    $pdo->majEtatFicheFrais($idVisiteur, $leMois, $etat);
    ?>
    <div class="alert alert-info" role="alert">
        <p>La fiche a bien remboursée! <a href="index.php">Cliquez ici</a>
            pour revenir à la page d'accueil.</p>
    </div>
    <?php
break;
}
