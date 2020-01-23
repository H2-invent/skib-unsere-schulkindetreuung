<?php

namespace App\Controller;

use App\Entity\Active;
use App\Entity\Kind;
use App\Entity\Organisation;
use App\Entity\Schule;
use App\Entity\Sepa;
use App\Entity\Zeitblock;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Contracts\Translation\TranslatorInterface;
use function Doctrine\ORM\QueryBuilder;

class WidgetController extends AbstractController
{
    /**
     * @Route("/org_child/show/widget/kidsToday", name="widget_kids_today")
     */
    public function index(Request $request, TranslatorInterface $translator)
    {
        $organisation = $this->getDoctrine()->getRepository(Organisation::class)->find($request->get('id'));
        if ($organisation != $this->getUser()->getOrganisation()) {
            throw new \Exception('Wrong Organisation');
        }
        $today = (new \DateTime())->format('w');
        if ($today == 0) {
            $today = 6;
        } else {
            $today = $today - 1;
        }

        $stadt = $this->getUser()->getStadt();
        $active = $this->getDoctrine()->getRepository(Active::class)->findActiveSchuljahrFromCity($stadt);
        $qb = $this->getDoctrine()->getRepository(Kind::class)->createQueryBuilder('k')
            ->innerJoin('k.zeitblocks', 'b')
            ->andWhere('b.wochentag = :wochentag')
            ->andWhere('b.active = :jahr');
        $schulen = $qb->expr()->orX();
        foreach ($organisation->getSchule() as $key => $data) {

            $schulen->add('b.schule = :schule' . $key);
            $qb->setParameter('schule' . $key, $data);
        };
        $qb->andWhere($schulen);
        $qb->andWhere('k.fin = true');


        $qb->setParameter('jahr', $active)
            ->setParameter('wochentag', $today);
        $query = $qb->getQuery();
        $kinder = $result = $query->getResult();


        return new JsonResponse(array('title' => $translator->trans('Anwesende Kinder heute'), 'small' => '', 'anzahl' => sizeof($kinder), 'symbol' => 'people'));
    }

    /**
     * @Route("/org_child/show/widget/kidsSchuljahr", name="widget_kids_schuljahr")
     */
    public function schuljahr(Request $request, TranslatorInterface $translator)
    {
        $organisation = $this->getDoctrine()->getRepository(Organisation::class)->find($request->get('id'));
        if ($organisation != $this->getUser()->getOrganisation()) {
            throw new \Exception('Wrong Organisation');
        }
        $today = (new \DateTime())->format('w');
        if ($today == 0) {
            $today = 6;
        } else {
            $today = $today - 1;
        }

        $stadt = $this->getUser()->getStadt();
        $active = $this->getDoctrine()->getRepository(Active::class)->findActiveSchuljahrFromCity($stadt);
        $qb = $this->getDoctrine()->getRepository(Kind::class)->createQueryBuilder('k')
            ->innerJoin('k.zeitblocks', 'b')
            ->andWhere('b.active = :jahr');
        $schulen = $qb->expr()->orX();
        foreach ($organisation->getSchule() as $key => $data) {

            $schulen->add('b.schule = :schule' . $key);
            $qb->setParameter('schule' . $key, $data);
        };
        $qb->andWhere($schulen);
        $qb->andWhere('k.fin = true');
        $qb->setParameter('jahr', $active);

        $query = $qb->getQuery();
        $kinder = $result = $query->getResult();


        return new JsonResponse(array('title' => $translator->trans('Kinder dieses Schuljahr'), 'small' => '', 'anzahl' => sizeof($kinder), 'symbol' => 'people'));
    }

    /**
     * @Route("/org_child/show/widget/kidsinSchule", name="widget_kids_schule")
     */
    public function childsInSchule(Request $request, TranslatorInterface $translator)
    {
        $organisation = $this->getDoctrine()->getRepository(Organisation::class)->find($request->get('org_id'));
        if ($organisation != $this->getUser()->getOrganisation()) {
            throw new \Exception('Wrong Organisation');
        }
        $today = (new \DateTime())->format('w');
        $stadt = $this->getUser()->getStadt();
        $active = $this->getDoctrine()->getRepository(Active::class)->findActiveSchuljahrFromCity($stadt);
        $schule = $this->getDoctrine()->getRepository(Schule::class)->findOneBy(array('organisation' => $organisation, 'id' => $request->get('schule_id')));
        $qb = $this->getDoctrine()->getRepository(Kind::class)->createQueryBuilder('k')
            ->innerJoin('k.zeitblocks', 'b')
            ->andWhere('b.active = :jahr')
            ->andWhere('b.schule = :schule')
            ->setParameter('schule', $schule);

        $qb->andWhere('k.fin = true');
        $qb->setParameter('jahr', $active);

        $query = $qb->getQuery();
        $kinder = $result = $query->getResult();
        return new JsonResponse(array('title' => $schule->getName(), 'small' => $translator->trans('Kinder angemeldet'), 'anzahl' => sizeof($kinder), 'symbol' => 'sports_handball'));

    }

    /**
     * @Route("/org_child/show/widget/stundenplan", name="widget_kids_stundenplan")
     */
    public function blockansicht(Request $request, TranslatorInterface $translator)
    {
        $organisation = $this->getDoctrine()->getRepository(Organisation::class)->find($request->get('org_id'));
        if ($organisation != $this->getUser()->getOrganisation()) {
            throw new \Exception('Wrong Organisation');
        }
        $today = (new \DateTime())->format('w');
        $stadt = $this->getUser()->getStadt();
        $active = $this->getDoctrine()->getRepository(Active::class)->findActiveSchuljahrFromCity($stadt);
        $qb = $this->getDoctrine()->getRepository(Zeitblock::class)->createQueryBuilder('b')
            ->andWhere('b.active = :jahr')
            ->andWhere('b.deleted = false')
            ->innerJoin('b.schule', 'schule')
            ->andWhere('schule.organisation =:org')
            ->setParameter('org', $organisation)
            ->setParameter('jahr', $active);
            if($request->get('schule_id')){
                $schule = $this->getDoctrine()->getRepository(Schule::class)->find($request->get('schule_id'));
                $qb->andWhere('b.schule =:schule')
                    ->setParameter('schule',$schule);
            }
        $blocks = $qb->getQuery()->getResult();

        $blocksRender = array();
        foreach ($blocks as $data) {
            $blocksRender[$data->getWochentag()][] = $data;
        }
        $schule = $this->getDoctrine()->getRepository(Schule::class)->findBy(array('organisation'=>$organisation));
        return $this->render('widget/blockContent.html.twig', array('org'=>$organisation,'blocks' => $blocksRender,'schule'=>$schule));

    }

    /**
     * @Route("/org_accounting/widget/overdueSepa", name="widget_overdue_sepa")
     */
    public function sepa(Request $request, TranslatorInterface $translator)
    {
        $organisation = $this->getDoctrine()->getRepository(Organisation::class)->find($request->get('org_id'));
        if ($organisation != $this->getUser()->getOrganisation()) {
            throw new \Exception('Wrong Organisation');
        }
        $lastMonth = (new \DateTime())->modify('first day of last Month');
        $lastDay = (new \DateTime())->modify('last day of last Month');
        $active = $this->getDoctrine()->getRepository(Active::class)->findSchuleBetweentwoDates($lastDay,$lastDay,$organisation->getStadt());
        $qb = $this->getDoctrine()->getRepository(Sepa::class)->createQueryBuilder('s');
        $qb->andWhere('s.von <= :today')
            ->andWhere('s.bis >= :today')
            ->andWhere('s.organisation = :org')
            ->setParameter('today', $lastMonth)
            ->setParameter('org', $organisation);
        $query = $qb->getQuery();
        $sepa = $result = $query->getResult();


        if (sizeof($sepa) == 0 && $active !== null) {
            return new JsonResponse(array('title' => $translator->trans('SEPA Lastschrift fällig'), 'small' => '', 'anzahl' => 1, 'symbol' => 'money'));
        } else {
            return 0;
        }
    }
}
