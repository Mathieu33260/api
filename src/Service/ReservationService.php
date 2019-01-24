<?php

namespace App\Service;

use App\Entity\Reservation;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;

class ReservationService
{
    /** @var EntityManagerInterface $em */
    private $em;

    /** @var HoraireService $horaireService */
    private $horaireService;

    public function __construct(EntityManagerInterface $em, HoraireService $horaireService)
    {
        $this->em = $em;
        $this->horaireService = $horaireService;
    }

    /**
     * @param $week
     * @throws \Exception
     */
    public function getResaByWeek($week)
    {
        /**
         * @var \DateTime $start
         * @var \DateTime $end
         */
        $start = $this->horaireService->getStartAndEndDate($week)['week_start'];
        $end = $this->horaireService->getStartAndEndDate($week)['week_end'];

        $query = "SELECT *
         FROM reservation r
         WHERE (CONVERT(r.heure, DATETIME) > CONVERT(:debut, DATETIME) AND CONVERT(r.heure, DATETIME) <= CONVERT(:fin, DATETIME))";

        $statement = $this->em->getConnection()->prepare($query);
        $statement->bindValue("debut", $start);
        $statement->bindValue("fin", $end);
        $statement->execute();

        $result = $statement->fetchAll();

        return $result;
    }
}