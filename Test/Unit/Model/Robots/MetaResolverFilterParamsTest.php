<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Test\Unit\Model\Robots;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\ResourceConnection;
use Laminas\Stdlib\Parameters;
use Panth\RobotsSeo\Helper\Config;
use Panth\RobotsSeo\Model\Robots\MetaResolver;
use Panth\RobotsSeo\Service\DirectiveValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MetaResolverFilterParamsTest extends TestCase
{
    private const TRACKING = 'utm_source,utm_medium,gclid,fbclid,msclkid,mc_cid';

    private function resolver(array $query, string $ignored = self::TRACKING): MetaResolver
    {
        $request = $this->createStub(HttpRequest::class);
        $parameters = new Parameters($query);
        $request->method('getQuery')->willReturn($parameters);
        $request->method('getPathInfo')->willReturn('/compete-track-tote.html');

        $config = $this->createStub(Config::class);
        $config->method('isEnabled')->willReturn(true);
        $config->method('isNoindexFiltered')->willReturn(true);
        $config->method('isNoindexSearchResults')->willReturn(true);
        $config->method('getDefaultDirective')->willReturn('index,follow');
        $config->method('getIgnoredQueryParams')->willReturn(
            $ignored === '' ? [] : explode(',', $ignored)
        );

        $connection = $this->createStub(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $connection->method('isTableExists')->willReturn(false);
        $resource = $this->createStub(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getTableName')->willReturn('panth_seo_override');

        return new MetaResolver(
            $resource,
            $request,
            $config,
            $this->createStub(ScopeConfigInterface::class),
            new DirectiveValidator()
        );
    }

    #[DataProvider('indexableQueryProvider')]
    public function testTrackingParametersStayIndexable(array $query): void
    {
        $this->assertSame('index,follow', $this->resolver($query)->resolve('product', 9, 1));
    }

    public static function indexableQueryProvider(): array
    {
        return [
            'no query' => [[]],
            'utm campaign' => [['utm_source' => 'newsletter', 'utm_medium' => 'email']],
            'google click id' => [['gclid' => 'ABC123']],
            'facebook click id' => [['fbclid' => 'XYZ']],
            'mailchimp' => [['mc_cid' => '123']],
            'mixed case' => [['UTM_Source' => 'newsletter']],
            'safe paging' => [['p' => '2']],
        ];
    }

    #[DataProvider('filteredQueryProvider')]
    public function testRealFiltersAreStillNoindexed(array $query): void
    {
        $this->assertSame('noindex,follow', $this->resolver($query)->resolve('category', 3, 1));
    }

    public static function filteredQueryProvider(): array
    {
        return [
            'attribute filter' => [['color' => '5']],
            'sort order' => [['product_list_order' => 'price']],
            'page size' => [['product_list_limit' => '36']],
            'tracking plus filter' => [['utm_source' => 'newsletter', 'color' => '5']],
        ];
    }

    public function testEmptyIgnoreListRestoresTheStrictBehaviour(): void
    {
        $resolver = $this->resolver(['utm_source' => 'newsletter'], '');

        $this->assertSame('noindex,follow', $resolver->resolve('product', 9, 1));
    }
}
