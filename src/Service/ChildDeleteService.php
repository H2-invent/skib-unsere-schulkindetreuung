<?php

namespace App\Service;

use App\Entity\Active;
use App\Entity\Kind;
use App\Entity\Log;
use App\Entity\Organisation;
use App\Entity\Stadt;

use App\Entity\Stammdaten;
use App\Entity\User;
use Beelab\Recaptcha2Bundle\Form\Type\RecaptchaType;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;


// <- Add this

class ChildDeleteService
{
    private $em;
    private $translator;
    private $templating;
    private $mailer;
    private $abschluss;
    private $parameterBag;
    private $logger;
    private FilesystemOperator $internFileSystem;

    public function __construct(FilesystemOperator $internFileSystem, LoggerInterface $logger, ParameterBagInterface $parameterBag, WorkflowAbschluss $workflowAbschluss, MailerService $mailer, Environment $environment, TranslatorInterface $translator, EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
        $this->translator = $translator;
        $this->templating = $environment;
        $this->mailer = $mailer;
        $this->abschluss = $workflowAbschluss;
        $this->parameterBag = $parameterBag;
        $this->logger = $logger;
        $this->internFileSystem = $internFileSystem;
    }

    public function deleteChild(Kind $kind, User $user)
    {

        try {
            $childHist = $this->em->getRepository(Kind::class)->findHistoryOfThisChild($kind);
            foreach ($childHist as $data){
                $data->setStartDate(null);
                $this->em->persist($data);
            }
            $this->em->flush();

            $message = 'child Deleted: Tracing' . $kind->getTracing() .
                'Name: ' . $kind->getVorname().' '.$kind->getNachname() . '; ' .
                'fos_user_id: ' . $user->getId() . '; ';
            $log = new Log();
            $log->setUser($user->getEmail());
            $log->setDate(new \DateTime());
            $log->setMessage($message);
            $this->em->persist($log);
            $this->em->flush();
            if ($this->parameterBag->get('noEmailOnDelete') == 0) {
                $this->sendEmail($kind->getEltern(), $kind, $kind->getSchule()->getOrganisation());
            }

            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }

    public function sendEmail(Stammdaten $stammdaten, Kind $kind, Organisation $organisation)
    {
        $mailBetreff = $this->translator->trans('Abmeldung der Schulkindbetreuung für ') . $kind->getVorname() . ' ' . $kind->getNachname();
        $mailContent = $this->templating->render('email/abmeldebestatigung.html.twig', array('eltern' => $stammdaten, 'kind' => $kind, 'org' => $organisation, 'stadt' => $organisation->getStadt()));
        $attachment = array();
        foreach ($organisation->getStadt()->getEmailDokumenteSchulkindbetreuungAbmeldung() as $att) {
            $attachment[] = array(
                'body' => $this->internFileSystem->read($att->getFileName()),
                'filename' => $att->getOriginalName(),
                'type' => $att->getType()
            );
        }
        $this->mailer->sendEmail(
            $kind->getSchule()->getOrganisation()->getName(),
            $kind->getSchule()->getOrganisation()->getEmail(),
            $stammdaten->getEmail(),
            $mailBetreff,
            $mailContent,
            $kind->getSchule()->getOrganisation()->getEmail(),
            $attachment
        );
        foreach ($stammdaten->getPersonenberechtigters() as $data){
            $this->mailer->sendEmail(
                $kind->getSchule()->getOrganisation()->getName(),
                $kind->getSchule()->getOrganisation()->getEmail(),
                $data->getEmail(),
                $mailBetreff,
                $mailContent,
                $kind->getSchule()->getOrganisation()->getEmail(),
                $attachment
            );
        }

    }
}
