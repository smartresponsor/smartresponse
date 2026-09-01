<?php

declare(strict_types=1);

namespace App\Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 3).'/tools/inspection/app-source-structure-guard.php';

final class AppSourceStructureTest extends TestCase
{
    public function testSourceTreeAndNamingFollowHostCanon(): void
    {
        self::assertSame(
            [],
            \inspectAppSourceStructure(dirname(__DIR__, 3)),
            'App source tree or naming drifted from the host canon.',
        );
    }

    public function testServiceInterfaceMirrorIsAccepted(): void
    {
        $root = $this->fixtureRoot();
        try {
            $this->writeFixture(
                $root,
                'src/ServiceInterface/Demo/DemoServiceInterface.php',
                "<?php\nnamespace App\\ServiceInterface\\Demo;\ninterface DemoServiceInterface {}\n",
            );

            self::assertSame([], \inspectAppSourceStructure($root));
        } finally {
            $this->removeDirectory($root);
        }
    }

    public function testProviderInterfaceMirrorIsAccepted(): void
    {
        $root = $this->fixtureRoot();
        try {
            $this->writeFixture(
                $root,
                'src/ProviderInterface/Demo/DemoProviderInterface.php',
                "<?php\nnamespace App\\ProviderInterface\\Demo;\ninterface DemoProviderInterface {}\n",
            );

            self::assertSame([], \inspectAppSourceStructure($root));
        } finally {
            $this->removeDirectory($root);
        }
    }

    public function testInterfaceInsideImplementationTreeIsRejected(): void
    {
        $root = $this->fixtureRoot();
        try {
            $this->writeFixture(
                $root,
                'src/Service/Demo/DemoServiceInterface.php',
                "<?php\nnamespace App\\Service\\Demo;\ninterface DemoServiceInterface {}\n",
            );

            $violations = \inspectAppSourceStructure($root);
            self::assertNotSame([], $violations);
            self::assertStringContainsString('mirrored *Interface tree', implode("\n", $violations));
        } finally {
            $this->removeDirectory($root);
        }
    }

    public function testProviderInsideServiceTreeIsRejected(): void
    {
        $root = $this->fixtureRoot();
        try {
            $this->writeFixture(
                $root,
                'src/Service/Demo/DemoProvider.php',
                "<?php\nnamespace App\\Service\\Demo;\nfinal class DemoProvider {}\n",
            );

            $violations = \inspectAppSourceStructure($root);
            self::assertNotSame([], $violations);
            self::assertStringContainsString('Provider symbols belong under src/Provider/', implode("\n", $violations));
        } finally {
            $this->removeDirectory($root);
        }
    }

    public function testDependencyOwnershipMustNotBeDoublePrefixedWithApp(): void
    {
        $root = $this->fixtureRoot();
        try {
            $this->writeFixture(
                $root,
                'src/Service/Retail/AppRetailPlacementReviewService.php',
                "<?php\nnamespace App\\Service\\Retail;\nfinal class AppRetailPlacementReviewService {}\n",
            );

            $violations = \inspectAppSourceStructure($root);
            self::assertNotSame([], $violations);
            self::assertStringContainsString('dependency ownership token without the App prefix', implode("\n", $violations));
        } finally {
            $this->removeDirectory($root);
        }
    }

    public function testLegacyTopLevelServiceTestsAreRejected(): void
    {
        $root = $this->fixtureRoot();
        try {
            $this->writeFixture(
                $root,
                'tests/Service/Demo/DemoServiceTest.php',
                "<?php\nnamespace App\\Tests\\Service\\Demo;\nfinal class DemoServiceTest {}\n",
            );

            $violations = \inspectAppSourceStructure($root);
            self::assertNotSame([], $violations);
            self::assertStringContainsString('tests/Unit/Service', implode("\n", $violations));
        } finally {
            $this->removeDirectory($root);
        }
    }

    public function testInterfaceUnderNeutralContractTreeIsRejected(): void
    {
        $root = $this->fixtureRoot();
        try {
            $this->writeFixture(
                $root,
                'src/Contract/Ui/DemoContract.php',
                "<?php\nnamespace App\\Contract\\Ui;\ninterface DemoContract {}\n",
            );

            $violations = \inspectAppSourceStructure($root);
            self::assertNotSame([], $violations);
            self::assertStringContainsString('explicit mirrored src/*Interface', implode("\n", $violations));
        } finally {
            $this->removeDirectory($root);
        }
    }

    public function testGitHubBrandCasingIsCanonical(): void
    {
        $root = $this->fixtureRoot();
        try {
            $this->writeFixture(
                $root,
                'src/Command/AppPublishGithubCommand.php',
                "<?php\nnamespace App\\Command;\nfinal class AppPublishGithubCommand {}\n",
            );

            $violations = \inspectAppSourceStructure($root);
            self::assertNotSame([], $violations);
            self::assertStringContainsString('canonical GitHub brand casing', implode("\n", $violations));
        } finally {
            $this->removeDirectory($root);
        }
    }

    public function testRetailPlacementServiceUsesDirectionBeforeDomain(): void
    {
        $root = $this->fixtureRoot();
        try {
            $this->writeFixture(
                $root,
                'src/Service/Placement/Retail/RetailPlacementReviewService.php',
                "<?php\nnamespace App\\Service\\Placement\\Retail;\nfinal class RetailPlacementReviewService {}\n",
            );

            self::assertSame([], \inspectAppSourceStructure($root));
        } finally {
            $this->removeDirectory($root);
        }
    }

    public function testDomainMustNotPrecedeDirectionInRetailPlacementTrees(): void
    {
        $root = $this->fixtureRoot();
        try {
            $this->writeFixture(
                $root,
                'src/Service/Retail/Placement/RetailPlacementReviewService.php',
                "<?php\nnamespace App\\Service\\Retail\\Placement;\nfinal class RetailPlacementReviewService {}\n",
            );
            $this->writeFixture(
                $root,
                'src/Dto/Retail/Placement/RetailPlacementAddressFormData.php',
                "<?php\nnamespace App\\Dto\\Retail\\Placement;\nfinal class RetailPlacementAddressFormData {}\n",
            );
            $this->writeFixture(
                $root,
                'src/Form/Retail/Placement/RetailPlacementAddressType.php',
                "<?php\nnamespace App\\Form\\Retail\\Placement;\nfinal class RetailPlacementAddressType {}\n",
            );

            $violations = \inspectAppSourceStructure($root);
            self::assertNotSame([], $violations);
            $message = implode("\n", $violations);
            self::assertStringContainsString('src/Service/Placement/Retail', $message);
            self::assertStringContainsString('src/Dto/Placement/Retail', $message);
            self::assertStringContainsString('src/Form/Placement/Retail', $message);
        } finally {
            $this->removeDirectory($root);
        }
    }

    private function fixtureRoot(): string
    {
        $root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'app-structure-'.bin2hex(random_bytes(8));
        if (!mkdir($root.DIRECTORY_SEPARATOR.'src', 0777, true) && !is_dir($root.DIRECTORY_SEPARATOR.'src')) {
            throw new \RuntimeException('Unable to create architecture fixture root.');
        }

        return $root;
    }

    private function writeFixture(string $root, string $relativePath, string $content): void
    {
        $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create architecture fixture directory.');
        }
        if (false === file_put_contents($path, $content)) {
            throw new \RuntimeException('Unable to write architecture fixture.');
        }
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }
        rmdir($directory);
    }
}
