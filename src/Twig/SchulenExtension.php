<?php
// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Active;
use App\Entity\Kind;
use App\Entity\Schule;
use App\Entity\Zeitblock;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SchulenExtension extends AbstractExtension
{
    private $em;
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    public function getFunctions()
    {
        return array(
            new TwigFunction('getAnzahlBeworben', array($this, 'getAnzahlBeworben')),
            new TwigFunction('getAnzahlBeworbenKids', array($this, 'getAnzahlBeworbenKids')),
        );
    }

    public function getAnzahlBeworben(Schule $schule)
    {

        try {
            $blocks = $this->em->getRepository(Zeitblock::class)->findBeworbenBlocksBySchule($schule);
        }catch (\Exception $exception){
            $blocks = array();
        }

        return $blocks;
    }
    public function getAnzahlBeworbenKids(Zeitblock $block)
    {

        try {
            $kids = $this->em->getRepository(Kind::class)->findBeworbenByZeitblock($block);
        }catch (\Exception $exception){
            $kids = array();
        }

        return $kids;
    }
}
