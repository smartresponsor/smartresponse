<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Vendoring\Entity\Vendor\VendorEntity;
use App\Walleting\Entity\Wallet;
use App\Walleting\Entity\WalletAccount;
use App\Walleting\Entity\WalletFunding;
use App\Walleting\Entity\WalletLedgerTransaction;
use App\Walleting\Entity\WalletPaymentInstrument;
use App\Walleting\Enum\WalletAccountCategory;
use App\Walleting\Enum\WalletPaymentInstrumentType;
use App\Walleting\Enum\WalletTransactionType;
use App\Walleting\Service\WalletPostingService;
use App\Walleting\ValueObject\Ledger\WalletPostingInstruction;
use App\Withdrawing\Entity\Withdrawal;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

final class WalletDemoFixtures extends Fixture implements FixtureGroupInterface
{
    private const int ACTOR_USER_ID = 1;
    private const string CURRENCY = 'USD';

    public function __construct(private readonly WalletPostingService $postingService)
    {
    }

    public static function getGroups(): array
    {
        return ['wallet-demo'];
    }

    public function load(ObjectManager $manager): void
    {
        $vendor = $manager->getRepository(VendorEntity::class)->findOneBy(['ownerUserId' => self::ACTOR_USER_ID]);
        if (!$vendor instanceof VendorEntity || !is_int($vendor->getId())) {
            throw new \RuntimeException('Wallet demo fixture requires the Accessing Admin vendor owned by user 1.');
        }

        $vendorId = $vendor->getId();
        $wallet = $manager->getRepository(Wallet::class)->findOneBy(['ownerType' => 'vendor', 'ownerId' => (string) $vendorId]);
        if (!$wallet instanceof Wallet) {
            $wallet = new Wallet('vendor', (string) $vendorId);
            $manager->persist($wallet);
        }

        $available = $this->account($manager, $wallet, 'available', WalletAccountCategory::Asset);
        $reserved = $this->account($manager, $wallet, 'reserved', WalletAccountCategory::Reserve);
        $clearing = $this->account($manager, $wallet, 'demo-clearing', WalletAccountCategory::Clearing);
        $instrument = $this->paymentInstrument($manager, $wallet, $vendorId);
        $manager->flush();

        $funding = $manager->getRepository(WalletFunding::class)->findOneBy(['idempotencyKey' => 'wallet-demo-v1-funding']);
        if (!$funding instanceof WalletFunding) {
            $funding = new WalletFunding($wallet, $instrument, 125_000, self::CURRENCY, 'wallet-demo-v1-funding');
            $funding->start();
            $manager->persist($funding);
            $manager->flush();

            $transaction = $this->postingService->post(
                WalletTransactionType::Credit,
                'wallet-demo-v1-funding-ledger',
                [new WalletPostingInstruction($available, 125_000), new WalletPostingInstruction($clearing, -125_000)],
                ['fixture' => 'wallet-demo', 'operation' => 'funding'],
            );
            $funding->succeed($transaction);
            $manager->flush();
        }

        $this->postOnce(
            $manager,
            'wallet-demo-v1-reserve-ledger',
            WalletTransactionType::Reserve,
            [new WalletPostingInstruction($available, -15_000), new WalletPostingInstruction($reserved, 15_000)],
            ['fixture' => 'wallet-demo', 'operation' => 'reserve'],
        );

        $withdrawal = $manager->getRepository(Withdrawal::class)->findOneBy(['idempotencyKey' => 'wallet-demo-v1-withdrawal']);
        if (!$withdrawal instanceof Withdrawal) {
            $withdrawal = new Withdrawal(
                'wallet',
                $wallet->id()->toRfc4122(),
                'vendor',
                (string) $vendorId,
                'bank:demo-checking-4242',
                7_500,
                self::CURRENCY,
                'wallet-demo-v1-withdrawal',
            );
            $withdrawal->reserve('wallet-demo-v1-reservation');
            $withdrawal->start('demo-rail-v1-0001');
            $withdrawal->succeed();
            $manager->persist($withdrawal);
            $manager->flush();
        }

        $this->postOnce(
            $manager,
            'wallet-demo-v1-withdrawal-ledger',
            WalletTransactionType::Debit,
            [new WalletPostingInstruction($available, -7_500), new WalletPostingInstruction($clearing, 7_500)],
            ['fixture' => 'wallet-demo', 'operation' => 'withdrawal'],
        );
    }

    private function account(ObjectManager $manager, Wallet $wallet, string $code, WalletAccountCategory $category): WalletAccount
    {
        $account = $manager->getRepository(WalletAccount::class)->findOneBy(['wallet' => $wallet, 'code' => $code, 'currency' => self::CURRENCY]);
        if ($account instanceof WalletAccount) {
            return $account;
        }

        $account = new WalletAccount($wallet, $code, self::CURRENCY, $category);
        $manager->persist($account);

        return $account;
    }

    private function paymentInstrument(ObjectManager $manager, Wallet $wallet, int $vendorId): WalletPaymentInstrument
    {
        $reference = 'wallet-demo-vendor-'.$vendorId.'-bank';
        $instrument = $manager->getRepository(WalletPaymentInstrument::class)->findOneBy(['provider' => 'demo', 'providerReference' => $reference]);
        if ($instrument instanceof WalletPaymentInstrument) {
            return $instrument;
        }

        $instrument = new WalletPaymentInstrument($wallet, WalletPaymentInstrumentType::BankAccount, 'demo', $reference, 'Demo checking •••• 4242');
        $manager->persist($instrument);

        return $instrument;
    }

    /**
     * @param non-empty-list<WalletPostingInstruction> $instructions
     * @param array<string, mixed>                     $metadata
     */
    private function postOnce(ObjectManager $manager, string $idempotencyKey, WalletTransactionType $type, array $instructions, array $metadata): WalletLedgerTransaction
    {
        $existing = $manager->getRepository(WalletLedgerTransaction::class)->findOneBy(['idempotencyKey' => $idempotencyKey]);
        if ($existing instanceof WalletLedgerTransaction) {
            return $existing;
        }

        return $this->postingService->post($type, $idempotencyKey, $instructions, $metadata);
    }
}
