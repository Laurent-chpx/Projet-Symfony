<?php

namespace App\Entity;

use App\Repository\FileRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FileRepository::class)]
class File
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nameHashed = null;

    #[ORM\Column(length: 255)]
    private ?string $nameOriginal = null;

    #[ORM\ManyToOne(inversedBy: 'files')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Comment $comment = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNameHashed(): ?string
    {
        return $this->nameHashed;
    }

    public function setNameHashed(string $nameHashed): static
    {
        $this->nameHashed = $nameHashed;

        return $this;
    }

    public function getNameOriginal(): ?string
    {
        return $this->nameOriginal;
    }

    public function setNameOriginal(string $nameOriginal): static
    {
        $this->nameOriginal = $nameOriginal;

        return $this;
    }

    public function getComment(): ?Comment
    {
        return $this->comment;
    }

    public function setComment(?Comment $comment): static
    {
        $this->comment = $comment;

        return $this;
    }
}
