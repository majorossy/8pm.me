<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Setup\Patch\Data;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

/**
 * Add Additional Artists Data Patch
 *
 * Adds 30 new artist categories with their studio albums.
 */
class AddAdditionalArtists implements DataPatchInterface
{
    /**
     * Additional artists with their studio albums.
     * Format: [name => ['url_key' => string, 'albums' => [[name, url_key], ...]]]
     */
    private const CATEGORY_STRUCTURE = [
        'Billy Strings' => [
            'url_key' => 'billystrings',
            'albums' => [
                ['Turmoil & Tinfoil', 'turmoiltinfoil'],
                ['Home', 'home'],
                ['Renewal', 'renewal'],
                ['Me/And/Dad', 'meanddad'],
                ['Highway Prayers', 'highwayprayers'],
            ],
        ],
        'Goose' => [
            'url_key' => 'goose',
            'albums' => [
                ['Moon Cabin', 'mooncabin'],
                ['Shenanigans Nite Club', 'shenanigansiteclub'],
                ['Dripfield', 'dripfield'],
                ['Everything Must Go', 'everythingmustgo'],
                ['Chain Yer Dragon', 'chainyerdragon'],
            ],
        ],
        'Grateful Dead' => [
            'url_key' => 'gratefuldead',
            'albums' => [
                ['The Grateful Dead', 'thegratefuldead'],
                ['Anthem of the Sun', 'anthemofthesun'],
                ['Aoxomoxoa', 'aoxomoxoa'],
                ["Workingman's Dead", 'workingmansdead'],
                ['American Beauty', 'americanbeauty'],
                ['Wake of the Flood', 'wakeoftheflood'],
                ['From the Mars Hotel', 'fromthemarshotel'],
                ['Blues for Allah', 'bluesforallah'],
                ['Terrapin Station', 'terrapinstation'],
                ['Shakedown Street', 'shakedownstreet'],
                ['Go to Heaven', 'gotoheaven'],
                ['In the Dark', 'inthedark'],
                ['Built to Last', 'builttolast'],
            ],
        ],
        'Phish' => [
            'url_key' => 'phish',
            'albums' => [
                ['Junta', 'junta'],
                ['Lawn Boy', 'lawnboy'],
                ['A Picture of Nectar', 'apictureofnectar'],
                ['Rift', 'rift'],
                ['Hoist', 'hoist'],
                ['Billy Breathes', 'billybreathes'],
                ['Story of the Ghost', 'storyoftheghost'],
                ['The Siket Disc', 'thesiketdisc'],
                ['Farmhouse', 'farmhouse'],
                ['Round Room', 'roundroom'],
                ['Undermind', 'undermind'],
                ['Joy', 'joy'],
                ['Fuego', 'fuego'],
                ['Big Boat', 'bigboat'],
                ['Sigma Oasis', 'sigmaoasis'],
                ['Sci-Fi Soldier', 'scifisoldier'],
            ],
        ],
        'Smashing Pumpkins' => [
            'url_key' => 'smashingpumpkins',
            'albums' => [
                ['Gish', 'gish'],
                ['Siamese Dream', 'siamesedream'],
                ['Mellon Collie and the Infinite Sadness', 'melloncollie'],
                ['Adore', 'adore'],
                ['Machina/The Machines of God', 'machinamachinesofgod'],
                ['Machina II/The Friends & Enemies of Modern Music', 'machinaii'],
                ['Zeitgeist', 'zeitgeist'],
                ['Oceania', 'oceania'],
                ['Monuments to an Elegy', 'monumentstoanelegy'],
                ['Shiny and Oh So Bright, Vol. 1', 'shinyandohsobright'],
                ['Cyr', 'cyr'],
                ['Atum: A Rock Opera in Three Acts', 'atum'],
                ['Aghori Mhori Mei', 'aghorimhorimei'],
            ],
        ],
        'Phil Lesh and Friends' => [
            'url_key' => 'philleshandfriends',
            'albums' => [
                ['There and Back Again', 'thereandbackagain'],
            ],
        ],
        'Widespread Panic' => [
            'url_key' => 'widespreadpanic',
            'albums' => [
                ['Space Wrangler', 'spacewrangler'],
                ['Widespread Panic', 'widespreadpanicalbum'],
                ['Everyday', 'everyday'],
                ["Ain't Life Grand", 'aintlifegrand'],
                ['Bombs & Butterflies', 'bombsbutterflies'],
                ["'Til the Medicine Takes", 'tilthemedicine'],
                ["Don't Tell the Band", 'donttelltheband'],
                ['Ball', 'ball'],
                ['Earth to America', 'earthtoamerica'],
                ['Free Somehow', 'freesomehow'],
                ['Dirty Side Down', 'dirtysidedown'],
                ['Street Dogs', 'streetdogs'],
                ['Snake Oil King', 'snakeoilking'],
                ['Hailbound Queen', 'hailboundqueen'],
            ],
        ],
        'moe.' => [
            'url_key' => 'moe',
            'albums' => [
                ['Fatboy', 'fatboy'],
                ['Headseed', 'headseed'],
                ['No Doy', 'nodoy'],
                ['Tin Cans and Car Tires', 'tincansandcartires'],
                ['Dither', 'dither'],
                ["Season's Greetings from Moe", 'seasonsgreetings'],
                ['Wormwood', 'wormwood'],
                ['The Conch', 'theconch'],
                ['Sticks & Stones', 'sticksstones'],
                ['What Happened To The La Las', 'whathappenedtothelalas'],
                ['No Guts No Glory', 'nogutsnoglory'],
                ['This Is Not, We Are', 'thisisnotweare'],
                ['Circle of Giants', 'circleofgiants'],
            ],
        ],
        'Mac Creek' => [
            'url_key' => 'maccreek',
            'albums' => [],
        ],
        "Joe Russo's Almost Dead" => [
            'url_key' => 'joerussosalmostdead',
            'albums' => [],
        ],
        'King Gizzard & The Lizard Wizard' => [
            'url_key' => 'kinggizzardthelizardwizard',
            'albums' => [
                ['12 Bar Bruise', '12barbruise'],
                ['Eyes Like the Sky', 'eyeslikethesky'],
                ['Float Along - Fill Your Lungs', 'floatalongfillyourlungs'],
                ['Oddments', 'oddments'],
                ["I'm in Your Mind Fuzz", 'iminyourmindfuzz'],
                ['Quarters!', 'quarters'],
                ['Paper Mache Dream Balloon', 'papermachedreamballoon'],
                ['Nonagon Infinity', 'nonagoninfinity'],
                ['Flying Microtonal Banana', 'flyingmicrotonalbanana'],
                ['Murder of the Universe', 'murderoftheuniverse'],
                ['Sketches of Brunswick East', 'sketchesofbrunswickeast'],
                ['Polygondwanaland', 'polygondwanaland'],
                ['Gumboot Soup', 'gumbootsoup'],
                ['Fishing for Fishies', 'fishingforfishies'],
                ["Infest the Rats' Nest", 'infesttheratsnest'],
                ['K.G.', 'kg'],
                ['L.W.', 'lw'],
                ['Butterfly 3000', 'butterfly3000'],
                ['Made in Timeland', 'madeintimeland'],
                ['Omnium Gatherum', 'omniumgatherum'],
                ['Ice, Death, Planets, Lungs, Mushrooms and Lava', 'icedeathplanets'],
                ['Laminated Denim', 'laminateddenim'],
                ['Changes', 'changes'],
                ['PetroDragonic Apocalypse', 'petrodragonicapocalypse'],
                ['The Silver Cord', 'thesilvercord'],
                ['Flight b741', 'flightb741'],
                ['Phantom Island', 'phantomisland'],
            ],
        ],
        'Ween' => [
            'url_key' => 'ween',
            'albums' => [
                ['GodWeenSatan: The Oneness', 'godweensatan'],
                ['The Pod', 'thepod'],
                ['Pure Guava', 'pureguava'],
                ['Chocolate and Cheese', 'chocolateandcheese'],
                ['12 Golden Country Greats', '12goldencountrygreats'],
                ['The Mollusk', 'themollusk'],
                ['White Pepper', 'whitepepper'],
                ['Quebec', 'quebec'],
                ['La Cucaracha', 'lacucaracha'],
            ],
        ],
        'John Mayer' => [
            'url_key' => 'johnmayer',
            'albums' => [
                ['Room for Squares', 'roomforsquares'],
                ['Heavier Things', 'heavierthings'],
                ['Continuum', 'continuum'],
                ['Battle Studies', 'battlestudies'],
                ['Born and Raised', 'bornandraised'],
                ['Paradise Valley', 'paradisevalley'],
                ['The Search for Everything', 'thesearchforeverything'],
                ['Sob Rock', 'sobrock'],
            ],
        ],
        'Ratdog' => [
            'url_key' => 'ratdog',
            'albums' => [
                ['Evening Moods', 'eveningmoods'],
            ],
        ],
        "Umphrey's McGee" => [
            'url_key' => 'umphreysmcgee',
            'albums' => [
                ['Greatest Hits Vol. III', 'greatesthitsvol3'],
                ['Local Band Does OK', 'localbanddoesok'],
                ['Anchor Drops', 'anchordrops'],
                ['Safety in Numbers', 'safetyinnumbers'],
                ['Mantis', 'mantis'],
                ['Death by Stereo', 'deathbystereo'],
                ['Similar Skin', 'similarskin'],
                ['The London Session', 'thelondonsession'],
                ['ZONKEY', 'zonkey'],
                ["It's Not Us", 'itsnotus'],
                ["It's You", 'itsyou'],
                ['Asking For a Friend', 'askingforafriend'],
            ],
        ],
        'Keller Williams' => [
            'url_key' => 'kellerwilliams',
            'albums' => [
                ['Freek', 'freek'],
                ['Buzz', 'buzz'],
                ['Spun', 'spun'],
                ['Breathe', 'breathe'],
                ['Loop', 'loop'],
                ['Laugh', 'laugh'],
                ['Dance', 'dance'],
                ['Home', 'homekeller'],
                ['Stage', 'stage'],
                ['Grass', 'grass'],
                ['Dream', 'dream'],
                ['12', 'twelve'],
                ['Odd', 'odd'],
                ['Thief', 'thief'],
                ['Kids', 'kids'],
                ['Bass', 'bass'],
                ['Pick', 'pick'],
                ['Dos', 'dos'],
                ['Raw', 'raw'],
                ['Sync', 'sync'],
                ['Speed', 'speed'],
            ],
        ],
        'Tedeschi Trucks Band' => [
            'url_key' => 'tedeschittrucksband',
            'albums' => [
                ['Revelator', 'revelator'],
                ['Let Me Get By', 'letmegetby'],
                ['Signs', 'signs'],
                ['I Am the Moon', 'iamthemoon'],
            ],
        ],
        'Leftover Salmon' => [
            'url_key' => 'leftoversalmon',
            'albums' => [
                ['Bridges to Bert', 'bridgestobert'],
                ['Euphoria', 'euphoria'],
                ['Nashville Sessions', 'nashvillesessions'],
                ['Leftover Salmon', 'leftoversalmonalbum'],
                ['Aquatic Hitchhiker', 'aquatichitchhiker'],
                ['High Country', 'highcountry'],
                ['Something Higher', 'somethinghigher'],
                ['Brand New Good Old Days', 'brandnewgoodolddays'],
            ],
        ],
        'Furthur' => [
            'url_key' => 'furthur',
            'albums' => [],
        ],
        'Yonder Mountain String Band' => [
            'url_key' => 'yondermountainstringband',
            'albums' => [
                ['Elevation', 'elevation'],
                ['Town By Town', 'townbytown'],
                ['Mountain Tracks: Volume 2', 'mountaintracks2'],
                ['Yonder Mountain String Band', 'yondermountainstringbandalbum'],
                ['The Show', 'theshow'],
                ['Black Sheep', 'blacksheep'],
                ["Love Ain't Love", 'loveaintlove'],
                ['Get Yourself Outside', 'getyourselfoutside'],
                ['Nowhere Next', 'nowherenext'],
            ],
        ],
        'My Morning Jacket' => [
            'url_key' => 'mymorningjacket',
            'albums' => [
                ['The Tennessee Fire', 'thetennesseefire'],
                ['At Dawn', 'atdawn'],
                ['It Still Moves', 'itstillmoves'],
                ['Z', 'zalbum'],
                ['Evil Urges', 'evilurges'],
                ['Circuital', 'circuital'],
                ['The Waterfall', 'thewaterfall'],
                ['The Waterfall II', 'thewaterfallii'],
                ['My Morning Jacket', 'mymorningjacketalbum'],
                ['Is', 'isalbum'],
            ],
        ],
        'Guster' => [
            'url_key' => 'guster',
            'albums' => [
                ['Parachute', 'parachute'],
                ['Goldfly', 'goldfly'],
                ['Lost and Gone Forever', 'lostandgoneforever'],
                ['Keep It Together', 'keepittogether'],
                ['Ganging Up on the Sun', 'ganginguponthesun'],
                ['Easy Wonderful', 'easywonderful'],
                ['Evermotion', 'evermotion'],
                ['Look Alive', 'lookalive'],
                ['Ooh La La', 'oohlala'],
            ],
        ],
        'Warren Zevon' => [
            'url_key' => 'warrenzevon',
            'albums' => [
                ['Wanted Dead or Alive', 'wanteddeadoralive'],
                ['Warren Zevon', 'warrenzevonalbum'],
                ['Excitable Boy', 'excitableboy'],
                ['Bad Luck Streak in Dancing School', 'badluckstreak'],
                ['The Envoy', 'theenvoy'],
                ['Sentimental Hygiene', 'sentimentalhygiene'],
                ['Transverse City', 'transversecity'],
                ['Mr. Bad Example', 'mrbadexample'],
                ['Mutineer', 'mutineer'],
                ["Life'll Kill Ya", 'lifellkillya'],
                ["My Ride's Here", 'myrideshere'],
                ['The Wind', 'thewind'],
            ],
        ],
        'Cabinet' => [
            'url_key' => 'cabinet',
            'albums' => [
                ['Cabinet', 'cabinetalbum'],
                ['Leap', 'leap'],
                ['Stand and Sway', 'standandsway'],
            ],
        ],
        'Dogs in a Pile' => [
            'url_key' => 'dogsinapile',
            'albums' => [
                ['Bloom', 'bloom'],
                ['Not Your Average Beagle', 'notyouraveragebeagle'],
            ],
        ],
        'God Street Wine' => [
            'url_key' => 'godstreetwine',
            'albums' => [
                ['Bag', 'bag'],
                ['$1.99 Romances', '199romances'],
                ['Red', 'red'],
                ['Who\'s Driving?', 'whosdriving'],
                ['Kristi Shot a Puppy', 'kristishotapuppy'],
                ['Hot! Sweet! and Juicy!', 'hotsweetandjuicy'],
            ],
        ],
        'Lettuce' => [
            'url_key' => 'lettuce',
            'albums' => [
                ['Outta Here', 'outtahere'],
                ['Rage!', 'rage'],
                ['Fly!', 'fly'],
                ['Crush', 'crush'],
                ['Witches Stew', 'witchesstew'],
                ['Elevate', 'elevate'],
                ['Resonate', 'resonate'],
                ['Unify', 'unify'],
                ['Cook', 'cook'],
            ],
        ],
        'Twiddle' => [
            'url_key' => 'twiddle',
            'albums' => [
                ['The Natural Evolution of Consciousness', 'naturalevolution'],
                ['Somewhere on the Mountain', 'somewhereonthemountain'],
                ['PLUMP Chapter One', 'plumpchapterone'],
                ['Plump, Chapters 1 & 2', 'plumpchapters12'],
                ['Every Last Leaf', 'everylastleaf'],
            ],
        ],
        'Matisyahu' => [
            'url_key' => 'matisyahu',
            'albums' => [
                ['Shake Off the Dust... Arise', 'shakeoffthedust'],
                ['Youth', 'youth'],
                ['Light', 'light'],
                ['Spark Seeker', 'sparkseeker'],
                ['Akeda', 'akeda'],
                ['Undercurrent', 'undercurrent'],
                ['Matisyahu', 'matisyahualbum'],
                ['Ancient Child', 'ancientchild'],
            ],
        ],
        'Rusted Root' => [
            'url_key' => 'rustedroot',
            'albums' => [
                ['Cruel Sun', 'cruelsun'],
                ['When I Woke', 'wheniwoke'],
                ['Remember', 'remember'],
                ['Rusted Root', 'rustedrootalbum'],
                ['Welcome to My Party', 'welcometomyparty'],
                ['Stereo Rodeo', 'stereorodeo'],
                ['The Movement', 'themovement'],
            ],
        ],
    ];

    private ModuleDataSetupInterface $moduleDataSetup;
    private CategoryFactory $categoryFactory;
    private CategoryRepositoryInterface $categoryRepository;
    private CollectionFactory $categoryCollectionFactory;
    private LoggerInterface $logger;

    /** @var array<string, int> Cache of url_key => category_id */
    private array $urlKeyCache = [];

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CategoryFactory $categoryFactory,
        CategoryRepositoryInterface $categoryRepository,
        CollectionFactory $categoryCollectionFactory,
        LoggerInterface $logger
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->categoryFactory = $categoryFactory;
        $this->categoryRepository = $categoryRepository;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function apply(): self
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        try {
            // Build url_key cache for idempotency checks
            $this->buildUrlKeyCache();

            // Find Artists root category
            $artistsRootId = $this->findArtistsRoot();
            if (!$artistsRootId) {
                throw new \RuntimeException('Artists root category not found. Run CreateCategoryStructure patch first.');
            }

            // Get current max position
            $artistPosition = $this->getMaxPosition($artistsRootId) + 1;

            // Create all artists and their albums
            foreach (self::CATEGORY_STRUCTURE as $artistName => $artistData) {
                $artistId = $this->createCategoryIfNotExists(
                    $artistName,
                    $artistData['url_key'],
                    $artistsRootId,
                    $artistPosition++,
                    ['is_artist' => 1, 'is_album' => 0, 'is_song' => 0]
                );

                // Create albums for this artist
                $albumPosition = 1;
                foreach ($artistData['albums'] as $albumData) {
                    [$albumName, $albumUrlKey] = $albumData;
                    $this->createCategoryIfNotExists(
                        $albumName,
                        $albumUrlKey,
                        $artistId,
                        $albumPosition++,
                        ['is_artist' => 0, 'is_album' => 1, 'is_song' => 0]
                    );
                }
            }

            $artistCount = count(self::CATEGORY_STRUCTURE);
            $albumCount = array_sum(array_map(fn($a) => count($a['albums']), self::CATEGORY_STRUCTURE));
            $this->logger->info(sprintf(
                'AddAdditionalArtists patch completed. Added %d artists and %d albums.',
                $artistCount,
                $albumCount
            ));
        } catch (\Exception $e) {
            $this->logger->error('Failed to add additional artists: ' . $e->getMessage());
            throw $e;
        }

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    /**
     * Build cache of existing url_keys to category IDs
     */
    private function buildUrlKeyCache(): void
    {
        $connection = $this->moduleDataSetup->getConnection();

        $attributeId = $connection->fetchOne(
            "SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'url_key' AND entity_type_id = 3"
        );

        if (!$attributeId) {
            return;
        }

        $select = $connection->select()
            ->from(['v' => 'catalog_category_entity_varchar'], ['entity_id', 'value'])
            ->where('v.attribute_id = ?', $attributeId)
            ->where('v.store_id = 0');

        $results = $connection->fetchPairs($select);
        foreach ($results as $entityId => $urlKey) {
            if ($urlKey) {
                $this->urlKeyCache[$urlKey] = (int) $entityId;
            }
        }
    }

    /**
     * Find the Artists root category
     */
    private function findArtistsRoot(): ?int
    {
        if (isset($this->urlKeyCache['artists'])) {
            return $this->urlKeyCache['artists'];
        }

        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToFilter('url_key', 'artists');
        $collection->addFieldToFilter('parent_id', 2);
        $collection->setPageSize(1);

        if ($collection->getSize() > 0) {
            return (int) $collection->getFirstItem()->getId();
        }

        return null;
    }

    /**
     * Get the maximum position of existing child categories
     */
    private function getMaxPosition(int $parentId): int
    {
        $connection = $this->moduleDataSetup->getConnection();
        $maxPosition = $connection->fetchOne(
            'SELECT MAX(position) FROM catalog_category_entity WHERE parent_id = ?',
            [$parentId]
        );

        return (int) ($maxPosition ?? 0);
    }

    /**
     * Create a category if it doesn't exist
     */
    private function createCategoryIfNotExists(
        string $name,
        string $urlKey,
        int $parentId,
        int $position,
        array $attributes = []
    ): int {
        // Check if category already exists by url_key
        if (isset($this->urlKeyCache[$urlKey])) {
            $categoryId = $this->urlKeyCache[$urlKey];
            $this->logger->debug(sprintf('Category "%s" already exists (ID: %d)', $name, $categoryId));
            return $categoryId;
        }

        // Get parent path
        $connection = $this->moduleDataSetup->getConnection();
        $parentPath = $connection->fetchOne(
            'SELECT path FROM catalog_category_entity WHERE entity_id = ?',
            [$parentId]
        );

        // Create new category
        $category = $this->categoryFactory->create();
        $category->setName($name);
        $category->setUrlKey($urlKey);
        $category->setParentId($parentId);
        $category->setIsActive(true);
        $category->setIncludeInMenu(true);
        $category->setPosition($position);
        $category->setPath($parentPath);

        // Set custom attributes
        foreach ($attributes as $key => $value) {
            $category->setData($key, $value);
        }

        $this->categoryRepository->save($category);

        $categoryId = (int) $category->getId();
        $this->urlKeyCache[$urlKey] = $categoryId;

        $this->logger->info(sprintf('Created category "%s" (ID: %d, url_key: %s)', $name, $categoryId, $urlKey));

        return $categoryId;
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies(): array
    {
        return [
            CreateCategoryStructure::class
        ];
    }

    /**
     * @inheritDoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
