<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Service\HoraireService;
use DateTime;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as SWG;

/**
 * @Route("/horaires")
 */
class HoraireController extends Controller
{
    /** @var HoraireService $horaireService */
    private $horaireService;

    public function __construct(HoraireService $horaireService)
    {
        $this->horaireService = $horaireService;
    }

    /**
     * @Route("/semaine/{week_number}", name="horaire_all")
     * @Method({"GET"})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Retourne toutes les horaires disponibles",
     * )
     * @throws \Exception
     */
    public function allAction($week_number)
    {
        if (!$this->horaireService->isCorrectWeekNumber($week_number)) {
            return new Response('Numero de semaine invalide (doit être >= 1 et < 53', 400);
        }

        $horaires = $this->horaireService->getAllHoraireAvailable($week_number);

        if (!$horaires) {
            return new Response('Aucune horaires trouvé',404);
        }

        return new Response(json_encode($horaires), 200);
    }

    /**
     * @Route("/valide", name="horaire_valide")
     * @Method({"POST"})
     *
     * @SWG\Response(
     *     response=200,
     *     description="L'horaire est valide et disponible",
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="Format de date invalide (doit être de la forme AAAA-MM-JJ HH:MM:SS) OU Horaire non valide car pas dans les bonnes tranches horaire (8h-12h 13h-18h du lundi au vendredi) OU Non disponible car il y a déjà une réservation",
     * )
     *
     * @SWG\Parameter(
     *     name="heure",
     *     in="query",
     *     description="Heure du rendez-vous",
     *     type="string",
     *     required=true
     * )
     */
    public function valideAction(Request $request)
    {
        try {
            $heure = new DateTime($request->get('heure'));
        } catch (\Exception $e) {
            return new Response('Format de date invalide (doit être de la forme AAAA-MM-JJ HH:MM:SS', 400);
        }

        if (!$this->horaireService->isValideHoraire($heure)) {
            return new Response('Horaire non valide car pas dans les bonnes tranches horaire (8h-12h 13h-18h du lundi au vendredi)', 400);
        }

        $resa = $this->getDoctrine()->getRepository(Reservation::class)->findBy(['heure' => $heure]);

        return $resa ? new Response('Non disponible car il y a déjà une réservation', 400) : new Response('Valide', 200);
    }
}