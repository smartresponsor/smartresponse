<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Vendoring\Entity\Vendor\VendorEntity;
use App\Walleting\Entity\Account;
use App\Walleting\Entity\Funding;
use App\Walleting\Entity\LedgerTransaction;
use App\Walleting\Entity\PaymentInstrument;
use App\Walleting\Entity\Wallet;
use App\Walleting\Enum\AccountCategory;
use App\Walleting\Enum\PaymentInstrumentType;
use App\Walleting\Enum\TransactionType;
use App\Walleting\Ledger\PostingInstruction;
use App\Walleting\Service\PostingService;
use App\Withdrawing\Entity\Withdrawal;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

final class WalletDemoFixtures extends Fixture implements FixtureGroupInterface
{
    private const int ACTOR_USER_ID = 1;
    private const string CURRENCY = 'USD';

    public function __construct(private readonly PostingService $postingService)
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

        $available = $this->account($manager, $wallet, 'available', AccountCategory::Asset);
        $reserved = $this->account($manager, $wallet, 'reserved', AccountCategory::Reserve);
        $clearing = $this->account($manager, $wallet, 'demo-clearing', AccountCategory::Clearing);
        $instrument = $this->paymentInstrument($manager, $wallet, $vendorId);
        $manager->flush();

        $funding = $manager->getRepository(Funding::class)->findOneBy(['idempotencyKey' => 'wallet-demo-v1-funding']);
        if (!$funding instanceof Funding) {
            $funding = new Funding($wallet, $instrument, 125_000, self::CURRENCY, 'wallet-demo-v1-funding');
            $funding->start();
            $manager->persist($funding);
            $manager->flush();

            $transaction = $this->postingService->post(
                TransactionType::Credit,
                'wallet-demo-v1-funding-ledger',
                [new PostingInstruction($available, 125_000), new PostingInstruction($clearing, -125_000)],
                ['fixture' => 'wallet-demo', 'operation' => 'funding'],
            );
            $funding->succeed($transaction);
            $manager->flush();
        }

        $this->postOnce(
            $manager,
            'wallet-demo-v1-reserve-ledger',
            TransactionType::Reserve,
            [new PostingInstruction($available, -15_000), new PostingInstruction($reserved, 15_000)],
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
            TransactionType::Debit,
            [new PostingInstruction($available, -7_500), new PostingInstruction($clearing, 7_500)],
            ['fixture' => 'wallet-demo', 'operation' => 'withdrawal'],
        );
    }

    private function account(ObjectManager $manager, Wallet $wallet, string $code, AccountCategory $category): Account
    {
        $account = $manager->getRepository(Account::class)->findOneBy(['wallet' => $wallet, 'code' => $code, 'currency' => self::CURRENCY]);
        if ($account instanceof Account) {
            return $account;
        }

        $account = new Account($wallet, $code, self::CURRENCY, $category);
        $manager->persist($account);

        return $account;
    }

    private function paymentInstrument(ObjectManager $manager, Wallet $wallet, int $vendorId): PaymentInstrument
    {
        $reference = 'wallet-demo-vendor-'.$vendorId.'-bank';
        $instrument = $manager->getRepository(PaymentInstrument::class)->findOneBy(['provider' => 'demo', 'providerReference' => $reference]);
        if ($instrument instanceof PaymentInstrument) {
            return $instrument;
        }

        $instrument = new PaymentInstrument($wallet, PaymentInstrumentType::BankAccount, 'demo', $reference, 'Demo checking •••• 4242');
        $manager->persist($instrument);

        return $instrument;
    }

    /** @param non-empty-list<PostingInstruction> $instructions */
    private function postOnce(ObjectManager $manager, string $idempotencyKey, TransactionType $type, array $instructions, array $metadata): LedgerTransaction
    {
        $existing = $manager->getRepository(LedgerTransaction::class)->findOneBy(['idempotencyKey' => $idempotencyKey]);
        if ($existing instanceof LedgerTransaction) {
            return $existing;
        }

        return $this->postingService->post($type, $idempotencyKey, $instructions, $metadata);
    }
}
