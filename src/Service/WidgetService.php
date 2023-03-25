<?php

namespace App\Service;

use App\Entity\Active;
use App\Entity\Schule;
use App\Entity\Zeitblock;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

class WidgetService
{
    private EntityManagerInterface $em;
    private ChildSearchService $childSearchService;
    private ChildInBlockService $childInBlockService;
    private $time = 600;

    public function __construct(EntityManagerInterface $entityManager, ChildSearchService $childSearchService, ChildInBlockService $childInBlockService)
    {
        $this->em = $entityManager;
        $this->childSearchService = $childSearchService;
        $this->childInBlockService = $childInBlockService;
    }

    public function calculateSchulen(Schule $schule, \DateTime $now): int
    {
        $cache = new FilesystemAdapter();
        $value = $cache->get('schule_' . $schule->getId(), function (ItemInterface $item) use ($schule, $now) {
            $item->expiresAfter($this->time);

            // ... do some HTTP request or heavy computations
            $active = $this->em->getRepository(Active::class)->findActiveSchuljahrFromCity($schule->getOrganisation()->getStadt());
            $kinder = $this->childSearchService->searchChild(array('schule' => $schule->getId(), 'schuljahr' => $active->getId()), $schule->getOrganisation(), false, null, $now);
            return sizeof($kinder);
        });
        return $value;
    }

    public function calculateSchulenToday(Schule $schule, \DateTime $now): int
    {
        $cache = new FilesystemAdapter();
        $value = $cache->get('schule_today_' . $schule->getId(), function (ItemInterface $item) use ($schule, $now) {
            $item->expiresAfter($this->time);
            $today = $now->format('w');
            if ($today == 0) {
                $today = 6;
            } else {
                $today = $today - 1;
            }
            $active = $this->em->getRepository(Active::class)->findActiveSchuljahrFromCity($schule->getOrganisation()->getStadt());
            $kinder = $this->childSearchService->searchChild(array('wochentag' => $today, 'schuljahr' => $active, 'schule' => $schule->getId()), $schule->getOrganisation(), false, null, $now);

            return sizeof($kinder);
        });
        return $value;
    }

    public function calcBlocksNumberNow(Zeitblock $zeitblock)
    {
        $now = new \DateTime();
        if ($zeitblock->getActive()->getBis() < $now) {
            $now = $zeitblock->getActive()->getBis();
        }
        if ($zeitblock->getActive()->getVon() > $now) {
            $now = $zeitblock->getActive()->getVon();
        }

        $cache = new FilesystemAdapter();
        $value = $cache->get('zeitblock_' . $zeitblock->getId(), function (ItemInterface $item) use ($zeitblock, $now) {
            $kinder = $this->childInBlockService->getCurrentChildOfZeitblock($zeitblock, $now);

            return sizeof($kinder);
        });
        return $value;
    }

    public function calcChildsFromSchuljahrAndCity(Active $active, \DateTime $dateTime)
    {
        $cache = new FilesystemAdapter();
        $value = $cache->get('schuljahr_' . $active->getId(), function (ItemInterface $item) use ($active, $dateTime) {
            $total = 0;
            foreach ($active->getStadt()->getSchules() as $data){
                $total +=  sizeof($this->childSearchService->searchChild(array( 'schuljahr' => $active->getId(), 'schule' =>$data->getId()), null, false, null, $dateTime));
            }


            return $total;
        });
        return $value;
    }

}