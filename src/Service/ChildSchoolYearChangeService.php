<?php

namespace App\Service;

use App\Entity\Kind;
use App\Entity\Log;

use App\Entity\Stammdaten;
use App\Entity\User;
use App\Form\Type\ChildChangeEmailType;
use App\Form\Type\ChildChangeSchoolyearType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


// <- Add this

class ChildSchoolYearChangeService
{
    private $em;
    private $translator;
    private $email;
    private $form;
    private ElternService $elternService;
    public function __construct(TranslatorInterface $translator, EntityManagerInterface $entityManager, AnmeldeEmailService $anmeldeEmailService, FormFactoryInterface $formBuilder, ElternService $elternService)
    {
        $this->em = $entityManager;
        $this->translator = $translator;
        $this->email = $anmeldeEmailService;
        $this->form = $formBuilder;
        $this->elternService = $elternService;
    }

    public function form(Kind $kind)
    {
        $input = array('schoolyear' => $kind->getKlasse());

        $form = $this->form->create(ChildChangeSchoolyearType::class, $input,array('kind'=>$kind));

        return $form;
    }


    public function changeSchoolyear(Kind $kind, $input, User $user)
    {

        $elternOne = $this->elternService->getLatestElternFromChild($kind);
        $kindernAll = $this->em->getRepository(Kind::class)->findBy(array('tracing' => $kind->getTracing()));

        $message = 'Schoolyear changed from ' . $kind->getKlasse() . ' to ' . $input['schoolyear'] . '; ' .
            'kind_id: ' . $kind->getId() . '; ' .
            'fos_user_id: ' . $user->getId() . '; ' .
            'Tracing: ' . $elternOne->getTracing();
        $log = new Log();
        $log->setUser($user->getEmail());
        $log->setDate(new \DateTime());
        $log->setMessage($message);
        $this->em->persist($log);

        foreach ($kindernAll as $data) {
            $data->setKlasse($input['schoolyear']);
            $this->em->persist($data);
        }
        $this->em->flush();

        $this->email->sendEmail($kind, $elternOne, $kind->getSchule()->getStadt(), $this->translator->trans('Das Schuljahr Ihres Kindes wurde von einem Mitarbeiter der betreuenden Organisation geändert. Aus diesem Grund senden wir Ihnen die Buchungsbstätigung dieses Kindes nochmals zu:', [], $elternOne->getLanguage()));
        $this->email->setBetreff($this->translator->trans('Das Schuljahr Ihres Kindes wurde von einem Mitarbeiter der betreuenden Organisation geändert.', [], $elternOne->getLanguage()));
        $this->email->send($kind, $this->elternService->getLatestElternFromChild($kind));

        return true;

    }
}
