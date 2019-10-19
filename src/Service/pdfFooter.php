<?php

namespace App\Service;

use App\Entity\Organisation;
use App\Entity\Stadt;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use WhiteOctober\TCPDFBundle\Controller\TCPDFController;

// <- Add this

class pdfFooter extends \TCPDF
{
    private $stadt;
    private $organisation;
    public function setStadt(Stadt $stadt){
        $this->stadt = $stadt
        ;
    }
    public function setOrganisation(Organisation $organisation){
     $this->organisation = $organisation;
    }

    public function Footer() {
        $this->Line(0, 99, 4, 99);
        $this->Line(0, 198, 4, 198);
        $this->SetFont('helvetica', '', 8);
        // Position at 15 mm from bottom
        $this->SetY(-25);
        // New line in footer
        // Set font

        $this->organisation = new Organisation();
        // Page number
        if($this->stadt){
            $this->Line(20,$this->getY(),$this->getRemainingWidth()+15,$this->getY());
            $this->MultiCell($this->getRemainingWidth(), 8,  $this->stadt->getName() .' | '.$this->stadt->getAdresse().' '.$this->stadt->getAdresszusatz().' | '.$this->stadt->getPlz() .' '.$this->stadt->getOrt() , 0, 'C', 0, 0, '', '', true, 0, false, true, 10, 'M');
            $this->Ln(4);
            $this->MultiCell($this->getRemainingWidth(), 8,  'Tel.: '.$this->stadt->getTelefon() .' | eMail: '.$this->stadt->getEmail() , 0, 'C', 0, 0, '', '', true, 0, false, true, 10, 'M');
            $this->Ln(4);
            $this->MultiCell($this->getRemainingWidth(), 8,  '#########Freitext#########', 0, 'C', 0, 0, '', '', true, 0, false, true, 10, 'M');
            $this->Ln(4);
            $this->SetFont('helvetica', '', 10);
            $this->MultiCell($this->getRemainingWidth(), 10,   '##############Freitaxt#########', 0, 'C', 0, 0, '', '', true, 0, false, true, 10, 'M');

        }elseif ($this->organisation){
            $this->Line(20,$this->getY(),200,$this->getY());
            $this->MultiCell($this->getRemainingWidth(), 8,  $this->organisation->getName() .' | '.$this->organisation->getAdresse().' '.$this->organisation->getAdresszusatz().' | '.$this->organisation->getPlz() .' '.$this->organisation->getOrt() , 0, 'C', 0, 0, '', '', true, 0, false, true, 10, 'M');
            $this->Ln(4);
            $this->MultiCell($this->getRemainingWidth(), 8,  'Tel.: '.$this->organisation->getTelefon().' | '.$this->organisation->getEmail().' | eMail: '.$this->organisation->getAnsprechpartner(), 0, 'C', 0, 0, '', '', true, 0, false, true, 10, 'M');
            $this->Ln(4);
            $this->MultiCell($this->getRemainingWidth(), 8,  '############Freitext##########', 0, 'C', 0, 0, '', '', true, 0, false, true, 10, 'M');
            $this->Ln(4);
            $this->SetFont('helvetica', '', 10);
            $this->MultiCell($this->getRemainingWidth(), 10,   '############Freitext##########', 0, 'C', 0, 0, '', '', true, 0, false, true, 10, 'M');

        }



    }

}