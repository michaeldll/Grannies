<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Partie;
use AppBundle\Form\PartieType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncode;

/**
 * Class JouerController
 * @package AppBundle\Controller
 * @Route("/jouer")
 */
class JouerController extends Controller
{
    /**
     * @Route("/nouvelle-partie")
     */
    public function nouvellePartieAction(Request $request)
    {
        $partie = new Partie();
        $form = $this->createForm(PartieType::class, $partie);

        $form->handleRequest($request); //synchronisation des données du formulaire avec l'objet $partie via le formType
        if ($form->isSubmitted() && $form->isValid())
        {
            //récupére la connexion à la BDD
            $em = $this->getDoctrine()->getManager();

            // initialisation des données de la partie

            //récupération de toutes les bornes
            $bornes = $em->getRepository("AppBundle:Borne")->findAll();

            $tborne=array(); //tableau qui sera sauvegardé dans la BDD
            $ordre = 1; //ordre des bornes
            foreach ($bornes as $borne)
            {
                $tborne[$ordre] = array('id_borne' => $borne->getId(),
                    'position' => 'neutre');
                $ordre ++;
            }
            //sauvegarde la liste des bornes dans ma partie
            $partie->setListeDesBornes($tborne);

            $cartes = $em->getRepository('AppBundle:Carte')->findAll();
            $tcarte = array();
            foreach ($cartes as $carte)
            {
                $tcarte[] = $carte->getId(); //sauvegarde les id des cartes dans un tableau
            }

            shuffle($tcarte); //mélange du tableau

            //distribution de la main de J1
            $mainJ1=array();
            for($i = 0; $i<6; $i++)
            {
                $mainJ1[] = $tcarte[$i];
            }
            $partie->setMainj1($mainJ1);

            //distributoon de la main de J2
            $mainJ2=array();
            for($i = 6; $i<12; $i++)
            {
                $mainJ2[] = $tcarte[$i];
            }
            $partie->setMainj2($mainJ2);

            $pioche=array();
            for($i = 12; $i < count($tcarte); $i++)
            {
                $pioche[] = $tcarte[$i];
            }
            $partie->setPioche($pioche);

            $partie->setTourJoueur($partie->getJoueur1());

            $terrain = array(
                'col1' => array(0,0,0),
                'col2' => array(0,0,0),
                'col3' => array(0,0,0),
                'col4' => array(0,0,0),
                'col5' => array(0,0,0),
                'col6' => array(0,0,0),
                'col7' => array(0,0,0),
                'col8' => array(0,0,0),
                'col9' => array(0,0,0)
            );
            $partie->setTerrainj1($terrain);
            $partie->setTerrainj2($terrain);

            $em->persist($partie);
            $em->flush();

            // redirection vers la distribution des cartes
            return $this->redirectToRoute("affiche_plateau", array('partie' => $partie->getId()) );
        }

        return $this->render(':JouerController:nouvelle_partie.html.twig', array(
            'form' => $form->createView()
        ));
    }
    /**
     * @Route("/notLoggedIn", name="notLoggedIn")
     */
    public function notLoggedIn()
    {
        return $this->render(':JouerController:notLoggedIn.html.twig', array(

        ));
    }

    /**
     * @Route("/afficher/{partie}", name="affiche_plateau")
     */
    public function afficherPlateauAction(Partie $partie)
    {
        // Afficher le plateau

        //récupérer cartes et bornes
        $em = $this->getDoctrine()->getManager();
        $cartes = $em->getRepository('AppBundle:Carte')->findAll();
        $bornes = $em->getRepository('AppBundle:Borne')->findAll();

        //construction d'un tableau d'ibjet carte dont l'index est id
        $tcartes = array();
        foreach ($cartes as $carte)
        {
            $tcartes[$carte->getId()] = $carte;
        }

        $tbornes = array();
        foreach ($bornes as $borne)
        {
            $tbornes[$borne->getId()] = $borne;
        }


        $montour = false;

        if ($this->getUser() == null){
            return $this->redirectToRoute('notLoggedIn');
        }

        if ($this->getUser()->getId() == $partie->getTourJoueur()->getId())
        {
            $montour = true;
            if ($partie->getTourJoueur()->getId() == $partie->getJoueur1()->getId())
            {
                //c'est le joueur 1
                $nomadversaire = 'j2';
                $nomencours = 'j1';
                $adversaire = $partie->getJoueur2();
                $mainencours = $partie->getMainj1();
                $terrainencours = $partie->getTerrainj1();
                $terrainadversaire = $partie->getTerrainj2();
            } else
            {
                //c'est le joueur 2
                $nomadversaire = 'j1';
                $nomencours = 'j2';
                $adversaire = $partie->getJoueur1();
                $mainencours = $partie->getMainj2();
                $terrainencours = $partie->getTerrainj2();
                $terrainadversaire = $partie->getTerrainj1();
            }

        } else
        {
            $montour = false; //ce n'est pas mon tour de jeu
            if ($this->getUser()->getId() == $partie->getJoueur1()->getId())
            {
                //c'est le joueur 1
                $nomadversaire = 'j2';
                $nomencours = 'j1';
                $adversaire = $partie->getJoueur2();
                $mainencours = $partie->getMainj1();
                $terrainencours = $partie->getTerrainj1();
                $terrainadversaire = $partie->getTerrainj2();
            } else
            {
                //c'est le joueur 2
                $nomadversaire = 'j1';
                $nomencours = 'j2';
                $adversaire = $partie->getJoueur1();
                $mainencours = $partie->getMainj2();
                $terrainencours = $partie->getTerrainj2();
                $terrainadversaire = $partie->getTerrainj1();
            }
        }


        return $this->render(':JouerController:afficher_plateau.html.twig', array(
            'partie' => $partie,
            'tcartes' => $tcartes,
            'tbornes' => $tbornes,
            'mainencours' => $mainencours,
            'terrainencours' => $terrainencours,
            'terrainadversaire' => $terrainadversaire,
            'adversaire' => $adversaire,
            'user'=> $this->getUser(),
            'montour' => $montour,
            'nomadversaire' => $nomadversaire,
            'nomencours' => $nomencours,
            'etatborne'=> $partie->getListeDesBornes()
        ));
    }

    /**
     * @Route("/ajax/actualiser", name="actualiser")
     */
    public function actualiserAction(Request $request)
    {
        $idpartie = $request->request->get('partie');
        $idjoueur = $request->request->get('idjoueur');

        $em = $this->getDoctrine()->getManager();

        $partie = $em->getRepository('AppBundle:Partie')->find($idpartie);

        if($idjoueur == $partie->getTourJoueur()->getId()){
            return new Response('ok', 200);
        }else{
            return new Response('not found',404);
        }
    }

    /**
     * @Route("/menu", name="menu")
     */
    public function menuAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $toutesParties = $em->getRepository('AppBundle:Partie')->findAll();
        $tparties = array();
        foreach ($toutesParties as $partie)
        {
            $tparties[$partie->getId()] = $partie;
        }

        return $this->render(':JouerController:menu.html.twig', array(
            'toutesparties' => $toutesParties,
            'user'=> $this->getUser(),
        ));
    }

    /**
     * @Route("/ajax/findejeu", name="findejeu")
     */
    public function findejeuAction(Request $request)
    {
        $idpartie = $request->request->get('partie');

        $em = $this->getDoctrine()->getManager();

        $partie = $em->getRepository('AppBundle:Partie')->find($idpartie);

        if($partie->getEncours() == 0){
            return new Response('ok', 200);
        }else{
            return new Response('not found',404);
        }
    }

    /**
     * @Route("/ajax/jouercarte", name="jouer_carte")
     */
    public function sauvegarderDeplacementAction(Request $request)
    {
        $colonne = $request->request->get('colonne');
        $idcarte = $request->request->get('carte');
        $idpartie = $request->request->get('partie');

        $em = $this->getDoctrine()->getManager();

        $partie = $em->getRepository('AppBundle:Partie')->find($idpartie);

        if ($this->getUser()->getId() == $partie->getJoueur1()->getId()) {
            $terrainJ1 = $partie->getTerrainj1();

            $i = 0;
            $carteplace = false;
            //sauvegarde l'id de la carte dans le terrain du joueur 1.
            while ($carteplace == false) {
                if ($terrainJ1['col' . $colonne][$i] == 0) {
                    //alors la zone est libre
                    $terrainJ1['col' . $colonne][$i] = $idcarte;
                    $carteplace = true;
                }
                $i++;
            }

            $mainj1 = $partie->getMainj1();

            $index = array_search($idcarte, $mainj1);
            unset($mainj1[$index]);
            $mainj1 = array_values($mainj1);

            //Supprimer la carte de la main du joueur.

            $partie->setTerrainj1($terrainJ1);
            $partie->setMainj1($mainj1);
        } else
        {
            $terrainJ2 = $partie->getTerrainj2();

            $i = 0;
            $carteplace = false;
            //sauvegarde l'id de la carte dans le terrain du joueur 1.
            while ($carteplace == false) {
                if ($terrainJ2['col' . $colonne][$i] == 0) {
                    //alors la zone est libre
                    $terrainJ2['col' . $colonne][$i] = $idcarte;
                    $carteplace = true;
                }
                $i++;
            }

            $mainj2 = $partie->getMainj2();

            $index = array_search($idcarte, $mainj2);
            unset($mainj2[$index]);
            $mainj2 = array_values($mainj2);

            //Supprimer la carte de la main du joueur.

            $partie->setTerrainj2($terrainJ2);
            $partie->setMainj2($mainj2);
        }
        $em->persist($partie);
        $em->flush();

        return new Response('ok', 200);
    }

    /**
     * @Route("/piocher/{partie}", name="jouer_piocher")
     */
    public function piocherAction(Partie $partie)
    {
        $pioche = $partie->getPioche();
        if($pioche==[]){
            $em = $this->getDoctrine()->getManager();
            $message = 'Pioche vide!';
            if ($this->getUser()->getId() == $partie->getJoueur1()->getId()) {
                $partie->setTourJoueur($partie->getJoueur2());
            } else
            {
                $partie->setTourJoueur($partie->getJoueur1());
            }

            $em->persist($partie);
            $em->flush();
            return $this->redirectToRoute('affiche_plateau', array(
                'partie' => $partie->getId()
            ));
        }
        var_dump($pioche);
        $carte = $pioche[0];
        unset($pioche[0]);
        $pioche = array_values($pioche);

        $em = $this->getDoctrine()->getManager();
        $partie->setPioche($pioche);
        if ($this->getUser()->getId() == $partie->getJoueur1()->getId()) {

            $mainJ1 = $partie->getMainj1();
            $mainJ1[] = $carte;
            $partie->setMainj1($mainJ1);
            $partie->setTourJoueur($partie->getJoueur2());
        } else
        {
            $mainJ2 = $partie->getMainj2();
            $mainJ2[] = $carte;
            $partie->setMainj2($mainJ2);
            $partie->setTourJoueur($partie->getJoueur1());
        }

        $em->persist($partie);
        $em->flush();

        return $this->redirectToRoute('affiche_plateau', array(
            'partie' => $partie->getId()
        ));
    }

    /**
     * @Route("/revendiquerBorne/{partie}/{borne}", name="jouer_revendiquer")
     */
    public function revendiquerBorneAction(Partie $partie, $borne)
    {
        //Récupérer cartes
        $em = $this->getDoctrine()->getManager();
        $cartes = $em->getRepository('AppBundle:Carte')->findAll();

        //Construction d'un tableau d'objet carte dont l'index est id
        $tcartes = array();
        foreach ($cartes as $carte)
        {
            $tcartes[$carte->getId()] = $carte;
        }

        var_dump('HERE COMES');
        var_dump($tcartes);
        var_dump('HERE ENDS');

        //Construction des terrains j1 et j2
        $tabTerrainj1 = $partie->getTerrainj1();
        $tabTerrainj2 = $partie->getTerrainj2();

        $colonne = $borne;

        //Cartes joueur 1 et 2
        $cartesj1 = $tabTerrainj1['col' . $colonne];
        $cartesj2 = $tabTerrainj2['col' . $colonne];

        sort($cartesj1);

        if ($cartesj1[0] == 0 || $cartesj1[1] == 0 || $cartesj1[2] == 0){
            return $this->redirectToRoute('affiche_plateau', array(
                'partie' => $partie->getId()
            ));
        }

        // Info cartes j1
        $cartej1_1 = $tcartes[$cartesj1[0]];
        $cartej1_2 = $tcartes[$cartesj1[1]];
        $cartej1_3 = $tcartes[$cartesj1[2]];

        // Info cartes j2
        $cartej2_1 = $tcartes[$cartesj2[0]];
        $cartej2_2 = $tcartes[$cartesj2[1]];
        $cartej2_3 = $tcartes[$cartesj2[2]];

        // Info bornes
        $listeBornes = $partie->getListeDesBornes();

        // Initialiser les variables combinaison, pas nécessaire?
        $niveauCombinaisonj1 = 0;
        $niveauCombinaisonj2 = 0;

        $sommej1 = $cartej1_1->getNumero()+$cartej1_2->getNumero()+$cartej1_3->getNumero();
        $sommej2 = $cartej2_1->getNumero()+$cartej2_2->getNumero()+$cartej2_3->getNumero();

        // ((temps de remise des trois cartes))

        //
        //
        // TESTS JOUEUR 1
        //
        //
        /* BRELAN */
        if ($cartej1_1->getNumero() == $cartej1_2->getNumero()
            && 
            $cartej1_2->getNumero() == $cartej1_3->getNumero()){
            
                $niveauCombinaisonj1 = 4; // Brelan
            
        }
        /* SUITE COULEUR */
        elseif (
            /* Vérifier si suite */
            $cartej1_1->getNumero()==$cartej1_2->getNumero()-1
            &&
            $cartej1_2->getNumero()==$cartej1_3->getNumero()-1
            && /* Vérifier si couleurs égales */
            $cartej1_1->getCouleur()->getId() == $cartej1_2->getCouleur()->getId()
            &&
            $cartej1_2->getCouleur()->getId() == $cartej1_3->getCouleur()->getId()){

                $niveauCombinaisonj1 = 5; // Suite Couleur

        }
        /* SUITE */
        elseif (
            /* Vérifier si suite */
            $cartesj1[0]==$cartesj1[1]-1 && $cartesj1[1]==$cartesj1[2]-1
            &&
            /* Vérifier si couleurs INégales */
            $cartej1_1->getCouleur()->getId() != $cartej1_2->getCouleur()->getId()
            ||
            $cartej1_2->getCouleur()->getId() != $cartej1_3->getCouleur()->getId()
            ){

                $niveauCombinaisonj1 = 2; // Suite

        }

        /* COULEUR */
        elseif ($cartej1_1->getCouleur()->getId() == $cartej1_2->getCouleur()->getId() && $cartej1_2->getCouleur()->getId() == $cartej1_3->getCouleur()->getId()) /*Couleurs égales*/
        {
            $niveauCombinaisonj1 = 3; // Couleur
        }

        /* SOMME */
        else{
            $niveauCombinaisonj1 = 1; // Somme
        }

        //
        //
        // TESTS JOUEUR 2
        //
        //

        if ($cartej2_1->getNumero()== $cartej2_2->getNumero()
            &&
            $cartej2_2->getNumero()==$cartej2_3->getNumero()){

            $niveauCombinaisonj2 = 4; // Brelan

        }
        elseif (
            /* Vérifier si suite */
            $cartej2_1->getNumero()==$cartej2_2->getNumero()-1
            &&
            $cartej2_2->getNumero()==$cartej2_3->getNumero()-1
            && /* Vérifier si couleurs égales */
            $cartej2_1->getCouleur()->getId() == $cartej2_2->getCouleur()->getId()
            &&
            $cartej2_2->getCouleur()->getId() == $cartej2_3->getCouleur()->getId()){

            $niveauCombinaisonj2 = 5; // Suite Couleur

        }
        elseif (
            /* Vérifier si suite */
            $cartesj2[0]==$cartesj2[1]-1 && $cartesj2[1]==$cartesj2[2]-1
            &&
            /* Vérifier si couleurs inégales */
            $cartej2_1->getCouleur()->getId() != $cartej2_2->getCouleur()->getId()
            ||
            $cartej2_2->getCouleur()->getId() != $cartej2_3->getCouleur()->getId()
        ){

            $niveauCombinaisonj2 = 2; // Suite

        }
        elseif ($cartej2_1->getCouleur()->getId() == $cartej2_2->getCouleur()->getId() && $cartej2_2->getCouleur()->getId() == $cartej2_3->getCouleur()->getId()) /*Couleurs égales*/
        {
            $niveauCombinaisonj2 = 3; // Couleur
        }
        else{
            $niveauCombinaisonj2 = 1; // Somme
        }

        //var_dump('combin j1'.$niveauCombinaisonj1);
        //var_dump('combin j2'.$niveauCombinaisonj1);

        //var_dump("somme j1".$sommej1);
        //var_dump("somme j2".$sommej2);


        //TEST REVENDICATION
        if ($niveauCombinaisonj1>$niveauCombinaisonj2){
            // Borne j1
            var_dump('BORNE NiveauCOMBIN J1');
            $listeBornes[$borne]['position']="j1";
            $partie->setListeDesBornes($listeBornes);
            $em->persist($partie);
            $em->flush();
        }
        elseif ($niveauCombinaisonj2>$niveauCombinaisonj1){
            // Borne j2
            var_dump('BORNE NiveauCOMBIN J2');
            $listeBornes[$borne]['position']="j2";
            $partie->setListeDesBornes($listeBornes);
            $em->persist($partie);
            $em->flush();
        }
        elseif ($niveauCombinaisonj1==$niveauCombinaisonj2){
            if ($sommej1 > $sommej2){
                //var_dump('J1 GANHE PAR SOMME');
                $listeBornes[$borne]['position']="j1";
                $partie->setListeDesBornes($listeBornes);
                $em->persist($partie);
                $em->flush();
            }elseif($sommej2 > $sommej1){
                //var_dump('J2 GANHE PAR SOMME');
                $listeBornes[$borne]['position']="j2";
                $partie->setListeDesBornes($listeBornes);
                $em->persist($partie);
                $em->flush();
            }
        }


        //
        // FIN DE JEU
        //

        //Tableau pour compter le nombre de bornes
        $tabBornes = array();

        //Tableau objet avec les bornes de chaque joueur
        var_dump('listebornes');
        var_dump($listeBornes);

        $tabBornesj1 = array();
        $tabBornesj2 = array();

        foreach ($listeBornes as $borneAdd){
            if ($borneAdd['position'] == 'j1'){
                $tabBornesj1[] = $borneAdd;
            }elseif ($borneAdd['position'] == 'j2'){
                $tabBornesj2[] = $borneAdd;
            }
        }

/*        var_dump('listebornes j1 j2 --');
        var_dump($tabBornesj1);
        var_dump($tabBornesj2);*/

        // Ajouter position bornes à un tableau pour le test vainqueur
        for ($i=1; $i<10; $i++){
            $tabBornes[] = $listeBornes[$i]['position'];
        }

        sort($tabBornesj1);
        sort($tabBornesj2);

//        // debug
//        var_dump('TABBORNES');
//        var_dump($tabBornes);
//        var_dump('TABBORNES ENDS');

        $numberOfOccurences = array_count_values($tabBornes);

        //Test fin de jeu
        if(in_array( 'j1', $tabBornes ) && in_array( 'j2', $tabBornes )){
            if ($numberOfOccurences['j1']==5){
                var_dump('PLAYER1WINS - 5 cartes');
                $partie->setGagnant($partie->getJoueur1()->getId());
                $partie->setEncours(0);
                $em->persist($partie);
                $em->flush();
            }elseif ($numberOfOccurences['j2']==5){
                var_dump('PLAYER2WINS - 5 cartes');
                $partie->setGagnant($partie->getJoueur2()->getId());
                $partie->setEncours(0);
                $em->persist($partie);
                $em->flush();
            }elseif ($numberOfOccurences['j1']==3) {
                if($tabBornesj1[0]['id_borne']==$tabBornesj1[1]['id_borne']-1 && $tabBornesj1[1]['id_borne']==$tabBornesj1[2]['id_borne']-1){
                    var_dump('j1 wins - 3 cartes');
                    $winner = $partie->getJoueur1();
                    $partie->setGagnant($winner);
                    $partie->setEncours(0);
                    $em->persist($partie);
                    $em->flush();
                }
            }elseif ($numberOfOccurences['j2']==3) {
                if($tabBornesj2[0]['id_borne']==$tabBornesj2[1]['id_borne']-1 && $tabBornesj2[1]['id_borne']==$tabBornesj2[2]['id_borne']-1){
                    var_dump('j2 wins - 3 cartes');
                    $partie->setGagnant($partie->getJoueur2()->getId());
                    $partie->setEncours(0);
                    $em->persist($partie);
                    $em->flush();
                }
            }
        }else{
            var_dump('doesntwork');
        }

        var_dump('shouldwork');

        return $this->redirectToRoute('affiche_plateau', array(
            'partie' => $partie->getId()
        ));
    }



}
