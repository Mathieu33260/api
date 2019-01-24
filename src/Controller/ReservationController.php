<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Service\HoraireService;
use App\Service\ReservationService;
use DateTime;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as SWG;

/**
 * @Route("/reservations")
 */
class ReservationController extends Controller
{
    /** @var ReservationService $reservationService */
    private $reservationService;

    /** @var HoraireService $horaireService */
    private $horaireService;

    public function __construct(ReservationService $reservationService, HoraireService $horaireService)
    {
        $this->reservationService = $reservationService;
        $this->horaireService = $horaireService;
    }

    /**
     * @Route("/semaine/{week_number}", name="resa_all")
     * @Method({"GET"})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Retourne toutes les rendez-vous d'une semaine",
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="Numero de semaine invalide (doit être >= 1 et < 53",
     * )
     *
     * @SWG\Response(
     *     response=404,
     *     description="Aucun rendez-vous trouve",
     * )
     * @throws \Exception
     */
    public function allAction($week_number)
    {
        if (!$this->horaireService->isCorrectWeekNumber($week_number)) {
            return new Response('Numero de semaine invalide (doit être >= 1 et < 53', 400);
        }

        /** @var Reservation $resa */
        $resas = $this->reservationService->getResaByWeek($week_number);

        if (!$resas) {
            return new Response('Aucune réservation trouvé',404);
        }

        return new Response(json_encode($resas), 200);
    }

    /**
     * @Route("/", name="resa_create")
     * @Method({"POST"})
     *
     * @SWG\Response(
     *     response=201,
     *     description="Cree un rendez-vous",
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="Format de date invalide (doit être de la forme AAAA-MM-JJ HH:MM:SS) OU Horaire invalide ou indisponible, vérifiez via /horaires/valide",
     * )
     *
     * @SWG\Parameter(
     *     name="nom",
     *     in="query",
     *     description="Nom du patient",
     *     type="string",
     *     required=true
     * )
     *
     * @SWG\Parameter(
     *     name="prenom",
     *     in="query",
     *     description="Prenom du patient",
     *     type="string",
     *     required=true
     * )
     *
     * @SWG\Parameter(
     *     name="tel",
     *     in="query",
     *     description="Telephone du patient",
     *     type="string",
     *     required=true
     * )
     *
     * @SWG\Parameter(
     *     name="heure",
     *     in="query",
     *     description="Date et heure du rendez-vous, au format YYYY-MM-DD HH:MM:SS",
     *     type="string",
     *     required=true
     * )
     * @throws \Exception
     */
    public function createAction(Request $request)
    {
        $nom = $request->get('nom');
        $prenom = $request->get('prenom');
        $tel = $request->get('tel');
        try {
            $heure = new DateTime($request->get('heure'));
        } catch (\Exception $e) {
            return new Response('Format de date invalide (doit être de la forme AAAA-MM-JJ HH:MM:SS)', 400);
        }

        if (!$this->horaireService->isValideHoraire($heure)) {
            return new Response('Horaire invalide ou indisponible, vérifiez via /horaires/valide', 400);
        }

        $entityManager = $this->getDoctrine()->getManager();

        $resa = new Reservation();
        $resa->setNom($nom);
        $resa->setPrenom($prenom);
        $resa->setTel($tel);
        $resa->setHeure($heure);

        $entityManager->persist($resa);
        $entityManager->flush();

        return new Response("Succes", 201);
    }

    /**
     * @Route("/{id}", name="resa_edit")
     * @Method({"PUT"})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Modifie un rendez-vous",
     * )
     *
     * @SWG\Response(
     *     response=404,
     *     description="Aucun rendez-vous trouvé",
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="Format de date invalide (doit être de la forme AAAA-MM-JJ HH:MM:SS) OU Horaire invalide ou indisponible, vérifiez via /horaires/valide",
     * )
     *
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     description="Id du rendez-vous",
     *     type="integer",
     *     required=true
     * )
     *
     * @SWG\Parameter(
     *     name="nom",
     *     in="query",
     *     description="Nom du patient",
     *     type="string",
     *     required=true
     * )
     *
     * @SWG\Parameter(
     *     name="prenom",
     *     in="query",
     *     description="Prenom du patient",
     *     type="string",
     *     required=true
     * )
     *
     * @SWG\Parameter(
     *     name="tel",
     *     in="query",
     *     description="Telephone du patient",
     *     type="string",
     *     required=true
     * )
     *
     * @SWG\Parameter(
     *     name="heure",
     *     in="query",
     *     description="Date et heure du rendez-vous, au format YYYY-MM-DD HH:MM:SS",
     *     type="string",
     *     required=true
     * )
     * @throws \Exception
     */
    public function editAction(Request $request, $id)
    {
        /** @var Reservation $resa */
        $resa = $this->getDoctrine()->getRepository(Reservation::class)->find($id);

        if (!$resa) {
            return new Response('Aucune réservation trouvé',404);
        }

        $nom = $request->get('nom');
        $prenom = $request->get('prenom');
        $tel = $request->get('tel');
        try {
            $heure = new DateTime($request->get('heure'));
        } catch (\Exception $e) {
            return new Response('Format de date invalide (doit être de la forme AAAA-MM-JJ HH:MM:SS', 400);
        }

        if (!$this->horaireService->isValideHoraire($heure)) {
            return new Response('Horaire invalide ou indisponible, vérifiez via /horaires/valide', 400);
        }

        $entityManager = $this->getDoctrine()->getManager();

        $resa->setNom($nom);
        $resa->setPrenom($prenom);
        $resa->setTel($tel);
        $resa->setHeure($heure);

        $entityManager->persist($resa);
        $entityManager->flush();

        return new Response("Succes", 200);
    }

    /**
     * @Route("/{id}", name="resa_delete")
     * @Method({"DELETE"})
     *
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     description="Id du rendez-vous",
     *     type="integer",
     *     required=true
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Supprime un rendez-vous",
     * )
     *
     * @SWG\Response(
     *     response=404,
     *     description="Aucun rendez-vous trouve",
     * )
     */
    public function deleteAction($id)
    {
        /** @var Reservation $resa*/
        $resa = $this->getDoctrine()->getRepository(Reservation::class)->find($id);

        if (!$resa) {
            return new Response('Aucune réservation trouvé',404);
        }

        $entityManager = $this->getDoctrine()->getManager();

        $entityManager->remove($resa);
        $entityManager->flush();

        return new Response("Succes", 200);
    }
}