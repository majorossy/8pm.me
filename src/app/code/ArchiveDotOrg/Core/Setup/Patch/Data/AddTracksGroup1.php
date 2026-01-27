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
 * Add Tracks Group 1 Data Patch
 *
 * Adds tracks for Billy Strings, Goose, Grateful Dead, and Phish albums.
 */
class AddTracksGroup1 implements DataPatchInterface
{
    /**
     * Track data structure
     * Format: Artist Name => Album Name => [[track_name, track_url_key], ...]
     */
    private const TRACKS = [
        'Billy Strings' => [
            'Turmoil & Tinfoil' => [
                ['On the Line', 'ontheline'],
                ['Meet Me at the Creek', 'meetmeatthecreek'],
                ['All of Tomorrow', 'alloftomorrow'],
                ['While I\'m Waiting Here', 'whileimwaitinghere'],
                ['Living Like an Animal', 'livinglikeananimal'],
                ['Turmoil & Tinfoil', 'turmoiltinfoil'],
                ['Salty Sheep', 'saltysheep'],
                ['Spinning', 'spinning'],
                ['Dealing Despair', 'dealingdespair'],
                ['Pyramid Country', 'pyramidcountry'],
                ['Doin\' Things Right', 'dointhingsright'],
                ['These Memories of You', 'thesememoriesofyou'],
            ],
            'Home' => [
                ['Taking Water', 'takingwater'],
                ['Must Be Seven', 'mustbeseven'],
                ['Running', 'running'],
                ['Away from the Mire', 'awayfromthemire'],
                ['Home', 'home'],
                ['Watch it Fall', 'watchitfall'],
                ['Long Forgotten Dream', 'longforgottendream'],
                ['Highway Hypnosis', 'highwayhypnosis'],
                ['Enough to Leave', 'enoughtoleave'],
                ['Hollow Heart', 'hollowheart'],
                ['Love Like Me', 'lovelikeme'],
                ['Everything\'s the Same', 'everythingsthesame'],
                ['Guitar Peace', 'guitarpeace'],
                ['Freedom', 'freedom'],
            ],
            'Renewal' => [
                ['Know It All', 'knowitall'],
                ['Secrets', 'secrets'],
                ['Love And Regret', 'loveandregret'],
                ['Heartbeat Of America', 'heartbeatofamerica'],
                ['In The Morning Light', 'inthemorninglight'],
                ['This Old World', 'thisoldworld'],
                ['Show Me The Door', 'showmethedoor'],
                ['Hellbender', 'hellbender'],
                ['Red Daisy', 'reddaisy'],
                ['The Fire On My Tongue', 'thefireonmytongue'],
                ['Nothing\'s Working', 'nothingsworking'],
                ['Hide And Seek', 'hideandseek'],
                ['Ice Bridges', 'icebridges'],
                ['Fire Line', 'fireline'],
                ['Running The Route', 'runningtheroute'],
                ['Leaders', 'leaders'],
            ],
            'Me/And/Dad' => [
                ['Long Journey Home', 'longjourneyhome'],
                ['Life to Go', 'lifetogo'],
                ['Way Downtown', 'waydowntown'],
                ['Little Blossom', 'littleblossom'],
                ['Peartree', 'peartree'],
                ['Stone Walls and Steel Bars', 'stonewallsandsteelbars'],
                ['Little White Church', 'littlewhitechurch'],
                ['Dig a Little Deeper (In the Well)', 'digalittledeeperinthewell'],
                ['Wandering Boy', 'wanderingboy'],
                ['I Haven\'t Seen Mary in Years', 'ihaven\'tseenmaryinyears'],
                ['Frosty Morn', 'frostymorn'],
                ['Catfish John', 'catfishjohn'],
                ['Blue Ridge Cabin Home', 'blueridgecabinhome'],
                ['I Heard My Mother Weeping', 'iheardmymotherweeping'],
            ],
            'Highway Prayers' => [
                ['Leaning on a Travelin\' Song', 'leaningonatravelinsong'],
                ['In the Clear', 'intheclear'],
                ['Escanaba', 'escanaba'],
                ['Gild the Lily', 'gildthelily'],
                ['Seven Weeks In County', 'sevenweeksincounty'],
                ['Stratosphere Blues / I Believe in You', 'stratospherebluesiibelieveinyou'],
                ['Cabin Song', 'cabinsong'],
                ['Don\'t Be Calling Me (at 4AM)', 'dontbecallingmeat4am'],
                ['Malfunction Junction', 'malfunctionjunction'],
                ['Catch and Release', 'catchandrelease'],
                ['Be Your Man', 'beyourman'],
                ['Gone a Long Time', 'gonealongtime'],
                ['It Ain\'t Before', 'itaintbefore'],
                ['My Alice', 'myalice'],
                ['Seney Stretch', 'seneystretch'],
                ['MORBUD4ME', 'morbud4me'],
                ['Leadfoot', 'leadfoot'],
                ['Happy Hollow', 'happyhollow'],
                ['The Beginning of the End', 'thebeginningoftheend'],
                ['Richard Petty', 'richardpetty'],
            ],
        ],
        'Goose' => [
            'Moon Cabin' => [
                ['Turned Clouds', 'turnedclouds'],
                ['Into The Myst', 'intothemyst'],
                ['Arcadia', 'arcadia'],
                ['Lead The Way', 'leadtheway'],
                ['Interlude I', 'interlude1'],
                ['Indian River', 'indianriver'],
                ['Interlude II', 'interlude2'],
                ['Jive I', 'jive1'],
                ['Rosewood Heart', 'rosewoodheart'],
                ['Interlude III', 'interlude3'],
                ['Jive II', 'jive2'],
            ],
            'Shenanigans Nite Club' => [
                ['So Ready', 'soready'],
                ['(s∆tellite)', 'satellite'],
                ['Madhuvan', 'madhuvan'],
                ['SOS', 'sos'],
                ['(dawn)', 'dawn'],
                ['Flodown', 'flodown'],
                ['Spirit Of The Dark Horse', 'spiritofthedarkhorse'],
                ['(7hunder)', 'thunder'],
                ['The Labyrinth', 'thelabyrinth'],
            ],
            'Dripfield' => [
                ['Borne', 'borne'],
                ['Hungersite', 'hungersite'],
                ['Dripfield', 'dripfield'],
                ['Slow Ready', 'slowready'],
                ['The Whales', 'thewhales'],
                ['Arrow', 'arrow'],
                ['Hot Tea', 'hottea'],
                ['Moonrise', 'moonrise'],
                ['Honeybee', 'honeybee'],
                ['726', 'album726'],
            ],
            'Everything Must Go' => [
                ['Everything Must Go', 'everythingmustgo'],
                ['Give It Time', 'giveittime'],
                ['Dustin Hoffman', 'dustinhoffman'],
                ['Your Direction', 'yourdirection'],
                ['Thatch', 'thatch'],
                ['Lead Up', 'leadup'],
                ['Animal', 'animal'],
                ['Red Bird', 'redbird'],
                ['Atlas Dogs', 'atlasdogs'],
                ['California Magic', 'californiamagic'],
                ['Feel It Now', 'feelitnow'],
                ['Iguana Song', 'iguanasong'],
                ['Silver Rising', 'silverrising'],
                ['How It Ends', 'howitends'],
            ],
            'Chain Yer Dragon' => [
                ['Hot Love & The Lazy Poet', 'hotlovethelazy poet'],
                ['Madalena', 'madalena'],
                ['Royal', 'royal'],
                ['Turbulence & The Night Rays', 'turbulencethenightrays'],
                ['Echoes of a Rose', 'echoesofarose'],
                ['Mr. Action', 'mraction'],
                ['.....', 'fiveperiods'],
                ['Dr. Darkness', 'drdarkness'],
                ['Empress of Organos', 'empressoforganos'],
                ['Jed Stone', 'jedstone'],
                ['Rockdale', 'rockdale'],
                ['Factory Fiction', 'factoryfiction'],
            ],
        ],
        'Grateful Dead' => [
            'The Grateful Dead' => [
                ['The Golden Road (To Unlimited Devotion)', 'thegoldenroadtounlimiteddevοtion'],
                ['Cold Rain and Snow', 'coldrainandsnow'],
                ['Good Morning Little School Girl', 'goodmorninglittleschoolgirl'],
                ['Beat It On Down the Line', 'beatitondowntheline'],
                ['Sitting on Top of the World', 'sittingontopoftheworld'],
                ['Cream Puff War', 'creampuffwar'],
                ['Morning Dew', 'morningdew'],
                ['New, New Minglewood Blues', 'newnewminglewoodblues'],
                ['Viola Lee Blues', 'violaleeblues'],
            ],
            'Anthem of the Sun' => [
                ['That\'s It for the Other One', 'thatsitfortheοtherone'],
                ['New Potato Caboose', 'newpotatocaboose'],
                ['Born Cross-Eyed', 'borncrosseyed'],
                ['Alligator', 'alligator'],
                ['Caution (Do Not Stop on Tracks)', 'cautiondonοtstopontracks'],
            ],
            'Aoxomoxoa' => [
                ['St. Stephen', 'ststephen'],
                ['Dupree\'s Diamond Blues', 'dupreesdiamondblues'],
                ['Rosemary', 'rosemary'],
                ['Doin\' That Rag', 'dοinthatrag'],
                ['Mountains of the Moon', 'mountainsofthemoon'],
                ['China Cat Sunflower', 'chinacatsunflower'],
                ['What\'s Become of the Baby', 'whatsbecomeofthebaby'],
                ['Cosmic Charlie', 'cosmiccharlie'],
            ],
            'Workingman\'s Dead' => [
                ['Uncle John\'s Band', 'unclejohnsband'],
                ['High Time', 'hightime'],
                ['Dire Wolf', 'direwolf'],
                ['New Speedway Boogie', 'newspeedwayboogie'],
                ['Cumberland Blues', 'cumberlandblues'],
                ['Black Peter', 'blackpeter'],
                ['Easy Wind', 'easywind'],
                ['Casey Jones', 'caseyjones'],
            ],
            'American Beauty' => [
                ['Box of Rain', 'boxofrain'],
                ['Friend of the Devil', 'friendofthedevil'],
                ['Sugar Magnolia', 'sugarmagnolia'],
                ['Operator', 'operator'],
                ['Candyman', 'candyman'],
                ['Ripple', 'ripple'],
                ['Brokedown Palace', 'brokedownpalace'],
                ['Till the Morning Comes', 'tillthemorningcomes'],
                ['Attics of My Life', 'atticsofmylife'],
                ['Truckin\'', 'truckin'],
            ],
            'Wake of the Flood' => [
                ['Mississippi Half-Step Uptown Toodeloo', 'mississippihalfstepuptowntoodeloo'],
                ['Let Me Sing Your Blues Away', 'letmesingyourbluesaway'],
                ['Row Jimmy', 'rowjimmy'],
                ['Stella Blue', 'stellablue'],
                ['Here Comes Sunshine', 'herecomessunshine'],
                ['Eyes of the World', 'eyesoftheworld'],
                ['Weather Report Suite', 'weatherreportsuite'],
            ],
            'From the Mars Hotel' => [
                ['U.S. Blues', 'usblues'],
                ['China Doll', 'chinadoll'],
                ['Unbroken Chain', 'unbrokenchain'],
                ['Loose Lucy', 'looselucy'],
                ['Scarlet Begonias', 'scarletbegonias'],
                ['Pride of Cucamonga', 'prideofcucamonga'],
                ['Money Money', 'moneymoney'],
                ['Ship of Fools', 'shipoffools'],
            ],
            'Blues for Allah' => [
                ['Help on the Way', 'helpontheway'],
                ['Slipknot!', 'slipknot'],
                ['Franklin\'s Tower', 'franklinstower'],
                ['King Solomon\'s Marbles', 'kingsolomonsmarbles'],
                ['The Music Never Stopped', 'themusicneverstopped'],
                ['Crazy Fingers', 'crazyfingers'],
                ['Sage & Spirit', 'sagespirit'],
                ['Blues for Allah', 'bluesforallah'],
                ['Sand Castles & Glass Camels', 'sandcastlesglasscamels'],
                ['Unusual Occurrences in the Desert', 'unusualoccurrencesinthedesert'],
            ],
            'Terrapin Station' => [
                ['Estimated Prophet', 'estimatedprophet'],
                ['Dancin\' in the Streets', 'dancininnthestreets'],
                ['Passenger', 'passenger'],
                ['Sunrise', 'sunrise'],
                ['Samson and Delilah', 'samsonanddelilah'],
                ['Terrapin Station', 'terrapinstation'],
            ],
            'Shakedown Street' => [
                ['Good Lovin\'', 'goodlovin'],
                ['France', 'france'],
                ['Shakedown Street', 'shakedownstreet'],
                ['Serengetti', 'serengetti'],
                ['Fire on the Mountain', 'fireonthemountain'],
                ['I Need a Miracle', 'ineedamiracle'],
                ['From the Heart of Me', 'fromtheheartofme'],
                ['Stagger Lee', 'staggerlee'],
                ['All New Minglewood Blues', 'allnewminglewoodblues'],
                ['If I Had the World to Give', 'ifihadtheworldtogive'],
            ],
            'Go to Heaven' => [
                ['Alabama Getaway', 'alabamagetaway'],
                ['Far from Me', 'farfromme'],
                ['Althea', 'althea'],
                ['Feel Like a Stranger', 'feellikeastranger'],
                ['Lost Sailor', 'lostsailor'],
                ['Saint of Circumstance', 'saintofcircumstance'],
                ['Antwerp\'s Placebo (The Plumber)', 'antwerpsplacebotheηlumber'],
                ['Easy to Love You', 'easytoloveyou'],
                ['Don\'t Ease Me In', 'donteasemein'],
            ],
            'In the Dark' => [
                ['Touch of Grey', 'touchofgrey'],
                ['Hell in a Bucket', 'hellinabucket'],
                ['When Push Comes to Shove', 'whenpushcomestoshove'],
                ['West L.A. Fadeaway', 'westlafadeaway'],
                ['Tons of Steel', 'tonsofsteel'],
                ['Throwing Stones', 'throwingstones'],
                ['Black Muddy River', 'blackmuddyriver'],
            ],
            'Built to Last' => [
                ['Foolish Heart', 'foolishheart'],
                ['Just a Little Light', 'justalittlelight'],
                ['Built to Last', 'builttolast'],
                ['Blow Away', 'blowaway'],
                ['Standing on the Moon', 'standingonthemoon'],
                ['Victim or the Crime', 'victimorthecrime'],
                ['We Can Run', 'wecanrun'],
                ['Picasso Moon', 'picassomoon'],
                ['I Will Take You Home', 'iwilltakeyouhome'],
            ],
        ],
        'Phish' => [
            'Junta' => [
                ['Fee', 'fee'],
                ['You Enjoy Myself', 'youenjoymyself'],
                ['Esther', 'esther'],
                ['Golgi Apparatus', 'golgiapparatus'],
                ['Foam', 'foam'],
                ['Dinner and a Movie', 'dinnerandamovie'],
                ['The Divided Sky', 'thedividedsky'],
                ['David Bowie', 'davidbowie'],
                ['Fluffhead', 'fluffhead'],
                ['Fluff\'s Travels', 'fluffstravels'],
                ['Contact', 'contact'],
            ],
            'Lawn Boy' => [
                ['The Squirming Coil', 'thesquirmingcoil'],
                ['Reba', 'reba'],
                ['My Sweet One', 'mysweetone'],
                ['Split Open and Melt', 'splitopenandmelt'],
                ['The Oh Kee Pah Ceremony', 'theohkeepahceremony'],
                ['Bathtub Gin', 'bathtubgin'],
                ['Run Like an Antelope', 'runlikeanantelope'],
                ['Lawn Boy', 'lawnboy'],
                ['Bouncing Around the Room', 'bouncingaroundtheroom'],
            ],
            'A Picture of Nectar' => [
                ['Llama', 'llama'],
                ['Eliza', 'eliza'],
                ['Cavern', 'cavern'],
                ['Poor Heart', 'poorheart'],
                ['Stash', 'stash'],
                ['Manteca', 'manteca'],
                ['Guelah Papyrus', 'guelahpapyrus'],
                ['Magilla', 'magilla'],
                ['The Landlady', 'thelandlady'],
                ['Glide', 'glide'],
                ['Tweezer', 'tweezer'],
                ['The Mango Song', 'themangosong'],
                ['Chalk Dust Torture', 'chalkdusttorture'],
                ['Faht', 'faht'],
                ['Catapult', 'catapult'],
                ['Tweezer Reprise', 'tweezerreprise'],
            ],
            'Rift' => [
                ['Rift', 'rift'],
                ['Fast Enough for You', 'fastenoughforyou'],
                ['Lengthwise', 'lengthwise'],
                ['Maze', 'maze'],
                ['Sparkle', 'sparkle'],
                ['Horn', 'horn'],
                ['The Wedge', 'thewedge'],
                ['My Friend, My Friend', 'myfriendmyfriend'],
                ['Weigh', 'weigh'],
                ['All Things Reconsidered', 'allthingsreconsidered'],
                ['Mound', 'mound'],
                ['It\'s Ice', 'itsice'],
                ['The Horse', 'thehorse'],
                ['Silent in the Morning', 'silentinthemorning'],
            ],
            'Hoist' => [
                ['Julius', 'julius'],
                ['Down with Disease', 'downwithdisease'],
                ['If I Could', 'ificould'],
                ['Riker\'s Mailbox', 'rikersmailbox'],
                ['Axilla (Part II)', 'axillapart2'],
                ['Lifeboy', 'lifeboy'],
                ['Sample in a Jar', 'sampleinajar'],
                ['Wolfman\'s Brother', 'wolfmansbrother'],
                ['Scent of a Mule', 'scentofamule'],
                ['Dog Faced Boy', 'dogfacedboy'],
                ['Demand', 'demand'],
            ],
            'Billy Breathes' => [
                ['Free', 'free'],
                ['Character Zero', 'characterzero'],
                ['Waste', 'waste'],
                ['Taste', 'taste'],
                ['Cars Trucks Buses', 'carstrucksbuses'],
                ['Talk', 'talk'],
                ['Theme from the Bottom', 'themefromthebottom'],
                ['Train Song', 'trainsong'],
                ['Bliss', 'bliss'],
                ['Billy Breathes', 'billybreathes'],
                ['Swept Away', 'sweptaway'],
                ['Steep', 'steep'],
                ['Prince Caspian', 'princecaspian'],
            ],
            'Story of the Ghost' => [
                ['Ghost', 'ghost'],
                ['Birds of a Feather', 'birdsofafeather'],
                ['Meat', 'meat'],
                ['Guyute', 'guyute'],
                ['Fikus', 'fikus'],
                ['Shafty', 'shafty'],
                ['Limb by Limb', 'limbbyýlimb'],
                ['Frankie Says', 'frankiesays'],
                ['Brian and Robert', 'brianandrobert'],
                ['Water in the Sky', 'waterinthesky'],
                ['Roggae', 'roggae'],
                ['Wading in the Velvet Sea', 'wadinginthevelvetsea'],
                ['The Moma Dance', 'themomadance'],
                ['End of Session', 'endofsession'],
            ],
            'The Siket Disc' => [
                ['What\'s the Use?', 'whatstheuse'],
                ['My Left Toe', 'mylefttoe'],
                ['The Happy Whip and Dung Song', 'thehappywhipanddungsong'],
                ['Quadrophonic Toppling', 'quadrophonictoppling'],
                ['Insects', 'insects'],
                ['Title Track', 'titletrack'],
                ['Albert', 'albert'],
                ['The Name is Slick', 'thenameisslick'],
                ['Farmhouse Recording Session', 'farmhouserecordingsession'],
            ],
            'Farmhouse' => [
                ['Farmhouse', 'farmhouse'],
                ['Twist', 'twist'],
                ['Bug', 'bug'],
                ['Back on the Train', 'backonthetrain'],
                ['Heavy Things', 'heavythings'],
                ['Gotta Jibboo', 'gottajibboo'],
                ['Dirt', 'dirt'],
                ['Piper', 'piper'],
                ['Sleep', 'sleep'],
                ['The Inlaw Josie Wales', 'theinlawjosiewales'],
                ['Sand', 'sand'],
                ['First Tube', 'firsttube'],
            ],
            'Round Room' => [
                ['Pebbles and Marbles', 'pebblesandmarbles'],
                ['Anything But Me', 'anythingbutme'],
                ['Round Room', 'roundroom'],
                ['Mexican Cousin', 'mexicancousin'],
                ['Friday', 'friday'],
                ['Seven Below', 'sevenbelow'],
                ['Mock Song', 'mocksong'],
                ['46 Days', '46days'],
                ['All of These Dreams', 'allofthesedreams'],
                ['Walls of the Cave', 'wallsofthecave'],
                ['Thunderhead', 'thunderhead'],
                ['Waves', 'waves'],
            ],
            'Undermind' => [
                ['Scents and Subtle Sounds (Intro)', 'scentsandsubtlesoundsintro'],
                ['Undermind', 'undermind'],
                ['The Connection', 'theconnection'],
                ['A Song I Heard the Ocean Sing', 'asongiheardtheoceansing'],
                ['Army of One', 'armyofone'],
                ['Crowd Control', 'crowdcontrol'],
                ['Maggie\'s Revenge', 'maggiesrevenge'],
                ['Nothing', 'nothing'],
                ['Two Versions of Me', 'twoversionsofme'],
                ['Access Me', 'accessme'],
                ['Scents and Subtle Sounds', 'scentsandsubtlesounds'],
                ['Tomorrow\'s Song', 'tomorrowssong'],
                ['Secret Smile', 'secretsmile'],
                ['Grind', 'grind'],
            ],
            'Joy' => [
                ['Backwards Down the Number Line', 'backwardsdownthenumberline'],
                ['Stealing Time from the Faulty Plan', 'stealingtimefromthefaultyplan'],
                ['Joy', 'joy'],
                ['Sugar Shack', 'sugarshack'],
                ['Ocelot', 'ocelot'],
                ['Kill Devil Falls', 'killdevilfalls'],
                ['Light', 'light'],
                ['I Been Around', 'ibeenarοund'],
                ['Time Turns Elastic', 'timeturнselastic'],
                ['Twenty Years Later', 'twentyyearslater'],
            ],
            'Fuego' => [
                ['Fuego', 'fuego'],
                ['The Line', 'theline'],
                ['Devotion to a Dream', 'devotiontoadream'],
                ['Halfway to the Moon', 'halfwaytothemoon'],
                ['Winterqueen', 'winterqueen'],
                ['Sing Monica', 'singmonica'],
                ['555', 'track555'],
                ['Waiting All Night', 'waitingallnight'],
                ['Wombat', 'wombat'],
                ['Wingsuit', 'wingsuit'],
            ],
            'Big Boat' => [
                ['Friends', 'friends'],
                ['Breath and Burning', 'breathandburning'],
                ['Home', 'homephish'],
                ['Blaze On', 'blazeon'],
                ['Tide Turns', 'tideturns'],
                ['Things People Do', 'thingspèopledo'],
                ['Waking Up Dead', 'wakingupdead'],
                ['Running Out of Time', 'runningoutoftime'],
                ['No Men in No Man\'s Land', 'nomeninnoмansland'],
                ['Miss You', 'missyou'],
                ['I Always Wanted It This Way', 'ialwayswantedítthisway'],
                ['More', 'more'],
                ['Petrichor', 'petrichor'],
            ],
            'Sigma Oasis' => [
                ['Sigma Oasis', 'sigmaoasis'],
                ['Leaves', 'leaves'],
                ['Everything\'s Right', 'everythingsright'],
                ['Mercury', 'mercury'],
                ['Shade', 'shade'],
                ['Evening Song', 'eveningsong'],
                ['Steam', 'steam'],
                ['A Life Beyond the Dream', 'alifebeyondthedream'],
                ['Thread', 'thread'],
            ],
            'Sci-Fi Soldier' => [
                ['Knuckle Bone Broth Avenue', 'knucklebonebοrothavenue'],
                ['Get More Down', 'getmoredown'],
                ['Egg in a Hole', 'egginahole'],
                ['Thanksgiving', 'thanksgiving'],
                ['Clear Your Mind', 'clearyourmind'],
                ['The 9th Cube', 'the9thcube'],
                ['The Inner Reaches of Outer', 'theinnerreachesοfouter'],
                ['Don\'t Doubt Me', 'dontdoubtme'],
                ['The Unwinding', 'theunwinding'],
                ['Something Living Here', 'somethinglivinghere'],
                ['The Howling', 'thehowling'],
                ['I Am in Miami', 'iaminmiami'],
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
            $this->buildUrlKeyCache();

            $totalTracks = 0;
            foreach (self::TRACKS as $artistName => $albums) {
                $artistId = $this->findArtistCategory($artistName);
                if (!$artistId) {
                    $this->logger->warning(sprintf('Artist category not found: %s', $artistName));
                    continue;
                }

                foreach ($albums as $albumName => $tracks) {
                    $albumId = $this->findAlbumCategory($albumName, $artistId);
                    if (!$albumId) {
                        $this->logger->warning(sprintf('Album category not found: %s - %s', $artistName, $albumName));
                        continue;
                    }

                    $trackPosition = 1;
                    foreach ($tracks as [$trackName, $trackUrlKey]) {
                        $this->createCategoryIfNotExists(
                            $trackName,
                            $trackUrlKey,
                            $albumId,
                            $trackPosition++,
                            ['is_artist' => 0, 'is_album' => 0, 'is_song' => 1]
                        );
                        $totalTracks++;
                    }
                }
            }

            $this->logger->info(sprintf(
                'AddTracksGroup1 completed. Added %d tracks for %d artists.',
                $totalTracks,
                count(self::TRACKS)
            ));
        } catch (\Exception $e) {
            $this->logger->error('Failed to add tracks (Group 1): ' . $e->getMessage());
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
     * Find artist category by name
     */
    private function findArtistCategory(string $artistName): ?int
    {
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToFilter('name', $artistName);
        $collection->addAttributeToFilter('is_artist', 1);
        $collection->setPageSize(1);

        if ($collection->getSize() > 0) {
            return (int) $collection->getFirstItem()->getId();
        }

        return null;
    }

    /**
     * Find album category by name under artist
     */
    private function findAlbumCategory(string $albumName, int $artistId): ?int
    {
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToFilter('name', $albumName);
        $collection->addAttributeToFilter('is_album', 1);
        $collection->addFieldToFilter('parent_id', $artistId);
        $collection->setPageSize(1);

        if ($collection->getSize() > 0) {
            return (int) $collection->getFirstItem()->getId();
        }

        return null;
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

        $this->logger->debug(sprintf('Created track "%s" (ID: %d)', $name, $categoryId));

        return $categoryId;
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies(): array
    {
        return [
            AddAdditionalArtists::class
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
