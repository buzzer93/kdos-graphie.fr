<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OrderArchiveRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderArchiveRepository::class)]
#[ORM\Table(name: 'order_archive')]
class OrderArchive
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $originalOrderId;

    #[ORM\Column(length: 50)]
    private string $reference;

    #[ORM\Column(length: 30)]
    private string $status;

    #[ORM\Column(length: 255)]
    private string $customerEmail;

    #[ORM\Column(type: Types::JSON)]
    private array $payload = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $archivedBy = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $reason = null;

    #[ORM\Column]
    private \DateTimeImmutable $archivedAt;

    public function __construct()
    {
        $this->archivedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOriginalOrderId(): int
    {
        return $this->originalOrderId;
    }

    public function setOriginalOrderId(int $originalOrderId): static
    {
        $this->originalOrderId = $originalOrderId;

        return $this;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCustomerEmail(): string
    {
        return $this->customerEmail;
    }

    public function setCustomerEmail(string $customerEmail): static
    {
        $this->customerEmail = $customerEmail;

        return $this;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function setPayload(array $payload): static
    {
        $this->payload = $payload;

        return $this;
    }

    public function getArchivedBy(): ?string
    {
        return $this->archivedBy;
    }

    public function setArchivedBy(?string $archivedBy): static
    {
        $this->archivedBy = $archivedBy;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): static
    {
        $this->reason = $reason;

        return $this;
    }

    public function getArchivedAt(): \DateTimeImmutable
    {
        return $this->archivedAt;
    }
}
