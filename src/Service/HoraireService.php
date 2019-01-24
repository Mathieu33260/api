<?php

namespace App\Service;

use App\Entity\Reservation;
use DateInterval;
use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\Types\Self_;

class HoraireService
{
    private const DUREE_RDV = 30;
    private const OUVERTURE = 8;
    private const FERMETURE = 18;
    private const DEBUT_PAUSE = 12;
    private const FIN_PAUSE = 13;

    /** @var EntityManagerInterface $em */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param $week
     * @return mixed
     * @throws \Exception
     */
    public function getStartAndEndDate($week)
    {
        $dto = new DateTime();
        $dto->setISODate($dto->format('Y'), $week);
        $checkDay = clone $dto;
        $checkDay->modify('+4 days');
        $checkDay->setTime(17, 30, 0);
        if ($this->isInPast($checkDay)) {
            $dto = new DateTime();
            $dto->modify('+1 year');
            $dto->setISODate($dto->format('Y'), $week);
        }
        $ret['week_start'] = $dto->format(\DateTime::ISO8601);
        $dto->modify('+4 days');
        $ret['week_end'] = $dto->format(\DateTime::ISO8601);
        return $ret;
    }

    /**
     * @param $week
     * @return array
     * @throws \Exception
     */
    public function getAllHoraireAvailable($week)
    {
        $start = new DateTime($this->getStartAndEndDate($week)['week_start']);
        $end = new DateTime($this->getStartAndEndDate($week)['week_end']);

        $horairesDispo = [];

        for ($day = clone $start; $day <= $end; $day->modify('+1 day')) {
            $ouverture = clone $day;
            $ouverture->setTime(self::OUVERTURE, 0, 0);

            $fermeture = clone $day;
            $fermeture->setTime(self::FERMETURE, 0, 0);

            $debutPause = clone $day;
            $debutPause->setTime(self::DEBUT_PAUSE, 0, 0);

            $finPause = clone $day;
            $finPause->setTime(self::FIN_PAUSE, 0, 0);

            for($heure = clone $ouverture; $heure <= $fermeture; $heure->add(DateInterval::createFromDateString(self::DUREE_RDV . ' minutes'))) {

                $copyHeure = clone $heure;

                if (($copyHeure >= $ouverture && $copyHeure < $debutPause)
                    || ($copyHeure >= $finPause && $copyHeure < $fermeture)) {

                    $resa = $this->em->getRepository(Reservation::class)->findBy(['heure' => $copyHeure]);

                    if (!$resa) {
                        $horairesDispo[$day->format('Y-m-d')][] = $copyHeure->format(\DateTime::ISO8601);
                    }
                }

            }
        }
        return $horairesDispo;
    }

    /**
     * @param DateTime $dateTime
     * @return bool
     * @throws \Exception
     */
    public function isInPast(DateTime $dateTime)
    {
        return $dateTime < new DateTime();
    }

    public function isCorrectWeekNumber($week)
    {
        return $week < 53 && $week >= 1;
    }

    public function isValideHoraire(DateTime $heure)
    {
        if ($heure->format('i') != 0 && $heure->format('i') != 30) {
            return false;
        }

        if ($heure->format('H') < 8
            || $heure->format('H') >= 18
            || ($heure->format('H') >= 12 && $heure->format('H') < 13)) {
            return false;
        }

        if ($heure->format('D') == 'Sat' || $heure->format('D') == 'Sun') {
            return false;
        }

        return true;
    }
}