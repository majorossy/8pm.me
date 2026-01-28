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
 * Add Tracks Group 2 Data Patch
 *
 * Adds tracks for Smashing Pumpkins, Widespread Panic, and John Mayer albums.
 */
class AddTracksGroup2 implements DataPatchInterface
{
    /**
     * Track data structure
     * Format: Artist Name => Album Name => [[track_name, track_url_key], ...]
     */
    private const TRACKS = [
        'Smashing Pumpkins' => [
            'Gish' => [
                ['I Am One', 'iamone'],
                ['Siva', 'siva'],
                ['Rhinoceros', 'rhinoceros'],
                ['Bury Me', 'buryme'],
                ['Crush', 'crush'],
                ['Suffer', 'suffer'],
                ['Snail', 'snail'],
                ['Tristessa', 'tristessa'],
                ['Window Paine', 'windowpaine'],
                ['Daydream', 'daydream'],
            ],
            'Siamese Dream' => [
                ['Cherub Rock', 'cherubrock'],
                ['Quiet', 'quiet'],
                ['Today', 'today'],
                ['Hummer', 'hummer'],
                ['Rocket', 'rocket'],
                ['Disarm', 'disarm'],
                ['Soma', 'soma'],
                ['Geek U.S.A.', 'geekusa'],
                ['Mayonaise', 'mayonaise'],
                ['Spaceboy', 'spaceboy'],
                ['Silverfuck', 'silverfuck'],
                ['Sweet Sweet', 'sweetsweet'],
                ['Luna', 'luna'],
            ],
            'Mellon Collie and the Infinite Sadness' => [
                ['Mellon Collie And The Infinite Sadness', 'melloncollieandtheinfinitesadness'],
                ['Tonight, Tonight', 'tonighttonight'],
                ['Jellybelly', 'jellybelly'],
                ['Zero', 'zero'],
                ['Here Is No Why', 'hereisnowhy'],
                ['Bullet With Butterfly Wings', 'bulletwithbutterflywings'],
                ['To Forgive', 'toforgive'],
                ['An Ode To No One', 'anodetonoone'],
                ['Love', 'love'],
                ['Cupid De Locke', 'cupiddelocke'],
                ['Galapogos', 'galapogos'],
                ['Muzzle', 'muzzle'],
                ['Porcelina Of The Vast Oceans', 'porcelinaofthevastoceans'],
                ['Take Me Down', 'takemedown'],
                ['Where Boys Fear To Tread', 'whereboysfeartotread'],
                ['Bodies', 'bodies'],
                ['Thirty-Three', 'thirtythree'],
                ['In The Arms Of Sleep', 'inthearmsοfsleep'],
                ['1979', 'track1979'],
                ['Tales Of A Scorched Earth', 'talesofascorchedearth'],
                ['Thru The Eyes Of Ruby', 'thrutheeyesofruby'],
                ['Stumbleine', 'stumbleine'],
                ['X.Y.U.', 'xyu'],
                ['We Only Come Out At Night', 'weonlycomeoutatnight'],
                ['Beautiful', 'beautiful'],
                ['Lily (My One And Only)', 'lilymyoneandonly'],
                ['By Starlight', 'bystarlight'],
                ['Farewell And Goodnight', 'farewellandgoodnight'],
            ],
            'Adore' => [
                ['To Sheila', 'tosheila'],
                ['Ava Adore', 'avaadore'],
                ['Perfect', 'perfect'],
                ['Daphne Descends', 'daphnedescends'],
                ['Once Upon a Time', 'onceuponatime'],
                ['Tear', 'tear'],
                ['Crestfallen', 'crestfallen'],
                ['Appels + Oranjes', 'appelsoranjes'],
                ['Pug', 'pug'],
                ['The Tale of Dusty and Pistol Pete', 'thetaleofdustyandpistolpete'],
                ['Annie-Dog', 'anniedog'],
                ['Shame', 'shame'],
                ['Behold! The Night Mare', 'beholdthenightmare'],
                ['For Martha', 'formartha'],
                ['Blank Page', 'blankpage'],
            ],
            'Machina/The Machines of God' => [
                ['The Everlasting Gaze', 'theeverlastinggaze'],
                ['Raindrops + Sunshowers', 'raindropssunshowers'],
                ['Stand Inside Your Love', 'standinsideyourlove'],
                ['I of the Mourning', 'iofthemourning'],
                ['The Sacred and Profane', 'thesacredandprofane'],
                ['Try, Try, Try', 'trytrytry'],
                ['Heavy Metal Machine', 'heavymetalmachine'],
                ['This Time', 'thistime'],
                ['The Imploding Voice', 'theimplodingvoice'],
                ['Glass and the Ghost Children', 'glassandtheghostchildren'],
                ['Wound', 'wound'],
                ['The Crying Tree of Mercury', 'thecryingtreeofmercury'],
                ['With Every Light', 'witheverylight'],
                ['Blue Skies Bring Tears', 'blueskiesbringtears'],
                ['Age of Innocence', 'ageofinnocence'],
            ],
            'Machina II/The Friends & Enemies of Modern Music' => [
                ['Slow Dawn', 'slowdawn'],
                ['Vanity', 'vanity'],
                ['Saturnine', 'saturnine'],
                ['Glass\' Theme (spacey version)', 'glassthemespaceyversion'],
                ['Soul Power (James Brown cover)', 'soulpowerjamesbrowncover'],
                ['Cash Car Star "Version 1"', 'cashcarstarversion1'],
                ['Lucky 13', 'lucky13'],
                ['Speed Kills', 'speedkills'],
                ['If There Is a God (piano/vox)', 'ifthereisagodpianovox'],
                ['Try, Try, Try (alt. music/lyrics)', 'trytrytryaltmusiclyrics'],
                ['Heavy Metal Machine (version I alt. mix)', 'heavymetalmachineversion1altmix'],
                ['Glass\' Theme', 'glasstheme'],
                ['Cash Car Star', 'cashcarstar'],
                ['Dross', 'dross'],
                ['Real Love', 'reallove'],
                ['Go', 'go'],
                ['Let Me Give the World to You', 'letmegivetheworldtoyou'],
                ['Innosense', 'innosense'],
                ['Home', 'homepumpkins'],
                ['Blue Skies Bring Tears (heavy)', 'blueskiesbringτearsheavy'],
                ['White Spyder', 'whitespyder'],
                ['In My Body', 'inmybody'],
                ['If There Is a God (full band)', 'ifthereisagodfullband'],
                ['Le Deux Machina (Synergy)', 'ledeuxmachinasynergy'],
                ['Atoms to Adam\'s', 'atomstoadams'],
            ],
            'Zeitgeist' => [
                ['Doomsday Clock', 'doomsdayclock'],
                ['7 Shades of Black', 'seνenshadesofblack'],
                ['Bleeding the Orchid', 'bleedingtheorchid'],
                ['That\'s the Way (My Love Is)', 'thatsthewaymyloveis'],
                ['Tarantula', 'tarantula'],
                ['Starz', 'starz'],
                ['United States', 'unitedstates'],
                ['Neverlost', 'neverlost'],
                ['Bring the Light', 'bringthelight'],
                ['(Come On) Let\'s Go!', 'comeonletsgo'],
                ['For God and Country', 'forgodandcountry'],
                ['Pomp and Circumstances', 'pompandcircumstances'],
            ],
            'Oceania' => [
                ['Quasar', 'quasar'],
                ['Panopticon', 'panopticon'],
                ['The Celestials', 'thecelestials'],
                ['Violet Rays', 'violetrays'],
                ['My Love Is Winter', 'myloveiswinter'],
                ['One Diamond, One Heart', 'onediamοndoneheart'],
                ['Pinwheels', 'pinwheels'],
                ['Oceania', 'oceania'],
                ['Pale Horse', 'palehorse'],
                ['The Chimera', 'thechimera'],
                ['Glissandra', 'glissandra'],
                ['Inkless', 'inkless'],
                ['Wildflower', 'wildflower'],
            ],
            'Monuments to an Elegy' => [
                ['Tiberius', 'tiberiuspumpkins'],
                ['Being Beige', 'beingbeige'],
                ['Anaise!', 'anaise'],
                ['One and All', 'oneandall'],
                ['Run2me', 'run2me'],
                ['Drum + Fife', 'drumfife'],
                ['Monuments', 'monuments'],
                ['Dorian', 'dorian'],
                ['Anti-Hero', 'antihero'],
            ],
            'Shiny and Oh So Bright, Vol. 1' => [
                ['Knights of Malta', 'knightsofmalta'],
                ['Silvery Sometimes (Ghosts)', 'silverysometimesghosts'],
                ['Travels', 'travels'],
                ['Solara', 'solara'],
                ['Alienation', 'alienation'],
                ['Marchin\' On', 'marchinon'],
                ['With Sympathy', 'withsympathy'],
                ['Seek and You Shall Destroy', 'seekandyoushalldestroy'],
            ],
            'Cyr' => [
                ['The Colour of Love', 'thecolouroflove'],
                ['Confessions of a Dopamine Addict', 'confessionsofadopamineaddict'],
                ['Cyr', 'cyr'],
                ['Dulcet in E', 'dulcetine'],
                ['Wrath', 'wrath'],
                ['Ramona', 'ramona'],
                ['Anno Satana', 'annosatana'],
                ['Birch Grove', 'birchgrove'],
                ['Wyttch', 'wyttch'],
                ['Starrcraft', 'starrcraft'],
                ['Purple Blood', 'purpleblood'],
                ['Save Your Tears', 'saveyourtears'],
                ['Telegenix', 'telegenix'],
                ['Black Forest, Black Hills', 'blackforestblackhills'],
                ['Adrennalynne', 'adrennalynne'],
                ['Haunted', 'haunted'],
                ['The Hidden Sun', 'thehiddensun'],
                ['Schaudenfreud', 'schaudenfreud'],
                ['Tyger, Tyger', 'tygertyger'],
                ['Minerva', 'minerva'],
            ],
            'Atum: A Rock Opera in Three Acts' => [
                ['Atum', 'atum'],
                ['Butterfly Suite', 'butterflysuite'],
                ['The Good in Goodbye', 'thegoodingoodbye'],
                ['Embracer', 'embracer'],
                ['With Ado I Do', 'withadoido'],
                ['Hooligan', 'hooligan'],
                ['Steps in Time', 'stepsintime'],
                ['Where Rain Must Fall', 'whererainmustfall'],
                ['Beyond the Vale', 'beyondthevale'],
                ['Hooray!', 'hooray'],
                ['The Gold Mask', 'thegoldmask'],
                ['Avalanche', 'avalanche'],
                ['Empires', 'empires'],
                ['Neophyte', 'neophyte'],
                ['Moss', 'moss'],
                ['Night Waves', 'nightwaves'],
                ['Space Age', 'spaceage'],
                ['Every Morning', 'everymorning'],
                ['To the Grays', 'tothegrays'],
                ['Beguiled', 'beguiled'],
                ['The Culling', 'theculling'],
                ['Springtimes', 'springtimes'],
                ['Sojourner', 'sojourner'],
                ['That Which Animates the Spirit', 'thatwhichanimatesthespirit'],
                ['The Canary Trainer', 'thecanarytrainer'],
                ['Pacer', 'pacer'],
                ['In Lieu of Failure', 'inlieuoffailure'],
                ['Fireflies', 'fireflies'],
                ['Spellbinding', 'spellbinding'],
                ['Of Wings', 'ofwings'],
                ['Cenotaph', 'cenotaph'],
                ['Harmageddon', 'harmageddon'],
                ['Embracer', 'embracer2'],
            ],
            'Aghori Mhori Mei' => [
                ['Edin', 'edin'],
                ['Pentagrams', 'pentagrams'],
                ['Sighommi', 'sighommi'],
                ['Pentecost', 'pentecost'],
                ['War Dreams of Itself', 'wardreamsofitself'],
                ['Who Goes There', 'whogoesthere'],
                ['999', 'track999'],
                ['Goeth the Fall', 'goeththefall'],
                ['Sicarus', 'sicarus'],
                ['Murnau', 'murnau'],
            ],
        ],
        'Widespread Panic' => [
            'Space Wrangler' => [
                ['Chilly Water', 'chillywater'],
                ['Travelin\' Light', 'travelinlight'],
                ['Space Wrangler', 'spacewrangler'],
                ['Coconut', 'coconut'],
                ['The Take Out', 'thetakeout'],
                ['Porch Song', 'porchsong'],
                ['Stop-Go', 'stopgo'],
                ['Driving Song', 'drivingsong'],
                ['Holden Oversoul', 'holdenoversoul'],
                ['Contentment Blues', 'contentmentblues'],
                ['Gomero Blanco', 'gomeroblanco'],
                ['Me and The Devil Blues / Heaven', 'meandthedevilbluesheaven'],
            ],
            'Widespread Panic' => [
                ['Walkin\' (For Your Love)', 'walkinforyourlove'],
                ['Pigeons', 'pigeons'],
                ['Mercy', 'mercy'],
                ['Makes Sense To Me', 'makessensetome'],
                ['C. Brown', 'cbrown'],
                ['Love Tractor', 'lovetractor'],
                ['Weight of the World', 'weightoftheworld'],
                ['I\'m Not Alone', 'imnotalone'],
                ['Barstools And Dreamers', 'barstoolsanddreamers'],
                ['Proving Ground', 'provingground'],
                ['The Last Straw', 'thelaststraw'],
                ['Send Your Mind', 'sendyourmind'],
            ],
            'Everyday' => [
                ['Pleas', 'pleas'],
                ['Hatfield', 'hatfield'],
                ['Wondering', 'wondering'],
                ['Papa\'s Home', 'papashome'],
                ['Diner', 'diner'],
                ['Pilgrims', 'pilgrims'],
                ['Pickin\' Up The Pieces', 'pickinupthepieces'],
                ['Postcard', 'postcard'],
                ['Everyday', 'everyday'],
                ['Henry Parsons Died', 'henryparsonsdied'],
                ['Fishwater', 'fishwater'],
            ],
            'Ain\'t Life Grand' => [
                ['Little Kin', 'littlekin'],
                ['Ain\'t Life Grand', 'aintlifegrand'],
                ['Airplane', 'airplane'],
                ['Can\'t Get High', 'cantgethigh'],
                ['Heroes', 'heroes'],
                ['Raise the Roof', 'raisetheroof'],
                ['Junior', 'junior'],
                ['L.A.', 'la'],
                ['Blackout Blues', 'blackoutblues'],
                ['Jack', 'jack'],
                ['Fishwater', 'fishwaterwsp2'],
            ],
            'Bombs & Butterflies' => [
                ['Radio Child', 'radiochild'],
                ['Aunt Avis', 'auntavis'],
                ['Tall Boy', 'tallboy'],
                ['Gradle', 'gradle'],
                ['Glory', 'glory'],
                ['Rebirtha', 'rebirtha'],
                ['You Got Yours', 'yougotyours'],
                ['Hope In A Hopeless World', 'hopeinahopelessworld'],
                ['Happy', 'happy'],
                ['Greta', 'greta'],
            ],
            '\'Til the Medicine Takes' => [
                ['Surprise Valley', 'surprisevalley'],
                ['Blue Indian', 'blueindian'],
                ['Party At Your Mama\'s House', 'partyatyourmamashouse'],
                ['All Time Low', 'alltimelow'],
                ['Climb to Safety', 'climbtosafety'],
                ['Nobody\'s Loss', 'nobodysloss'],
                ['Dyin\' Man', 'dyinman'],
                ['The Waker', 'thewaker'],
                ['One Arm Steve', 'onearmsteve'],
                ['You\'ll Be Fine', 'youllbefine'],
                ['Bear\'s Gone Fishin\'', 'bearsgonefishin'],
                ['Christmas Katie', 'christmaskatie'],
            ],
            'Don\'t Tell the Band' => [
                ['Little Lilly', 'littlelilly'],
                ['Give', 'give'],
                ['Imitation Leather Shoes (Edit)', 'imitationleathershoesedit'],
                ['This Part of Town', 'thispartoftown'],
                ['Sometimes', 'sometimes'],
                ['Thought Sausage', 'thoughtsausage'],
                ['Down', 'down'],
                ['Big Wooly Mammoth / Tears of a Woman', 'bigwoolymammothtearsofawoman'],
                ['Casa Del Grillo', 'casadelgrillo'],
                ['Old Joe', 'oldjoe'],
                ['Action Man', 'actionman'],
                ['Don\'t Tell the Band', 'donttelltheband'],
            ],
            'Ball' => [
                ['Fishing', 'fishing'],
                ['Thin Air (Smells Like Mississippi)', 'thinairsmellslikemississippi'],
                ['Tortured Artist', 'torturedartist'],
                ['Papa Johnny Road - Edit', 'papajohnnyrοadedit'],
                ['Sparks Fly', 'sparksfly'],
                ['Counting Train Cars', 'countingtraincars'],
                ['Don\'t Wanna Lose You', 'dontwannaloseyou'],
                ['Longer Look', 'longerlook'],
                ['Meeting of the Waters', 'meetingofthewaters'],
                ['Nebulous', 'nebulous'],
                ['Monstrosity', 'monstrosity'],
                ['Time Waits', 'timewaits'],
                ['Travelin\' Man', 'travelinman'],
            ],
            'Earth to America' => [
                ['Second Skin', 'secondskin'],
                ['Goodpeople', 'goodpeople'],
                ['From The Cradle', 'fromthecradle'],
                ['Solid Rock', 'solidrock'],
                ['Time Zones', 'timezones'],
                ['When the Clowns Come Home', 'whentheclownscomehome'],
                ['Ribs and Whiskey', 'ribsandwhiskey'],
                ['Crazy', 'crazy'],
                ['You Should Be Glad', 'youshouldbeglad'],
                ['May Your Glass Be Filled', 'mayyourglassbefilled'],
            ],
            'Free Somehow' => [
                ['Boom Boom Boom', 'boomboomboom'],
                ['Walk On the Flood', 'walkontheflood'],
                ['Angels On High', 'angelsonhigh'],
                ['Three Candles', 'threecandles'],
                ['Tickle the Truth', 'ticklethetruth'],
                ['Free Somehow', 'freesomehow'],
                ['Flicker', 'flicker'],
                ['Dark Day Program', 'darkdayprogram'],
                ['Her Dance Needs No Body', 'herdanceneedsnobody'],
                ['Already Fried', 'alreadyfried'],
                ['Up All Night', 'upallnight'],
            ],
            'Dirty Side Down' => [
                ['Saint Ex', 'saintex'],
                ['North', 'north'],
                ['Dirty Side Down', 'dirtysidedown'],
                ['This Cruel Thing', 'thiscruelthing'],
                ['Visiting Day', 'visitingday'],
                ['Clinic Cynic', 'cliniccynic'],
                ['St. Louis', 'stlouis'],
                ['Shut Up and Drive', 'shutupanddrive'],
                ['True to My Nature', 'truetomynature'],
                ['When You Coming Home', 'whenyoucominghome'],
                ['Jaded Tourist', 'jadedtourist'],
                ['Cotton Was King', 'cottonwasking'],
            ],
            'Street Dogs' => [
                ['Sell Sell', 'sellsell'],
                ['Steven\'s Cat', 'stevenscat'],
                ['Cease Fire', 'ceasefire'],
                ['Jamais Vu (The World Has Changed)', 'jamaisvutheworldhaschanged'],
                ['Angels Don\'t Sing The Blues', 'angelsdontsingtheblues'],
                ['Honky Red', 'honkyred'],
                ['The Poorhouse Of Positive Thinking', 'thepoοrhouseofpositivethinking'],
                ['Welcome To My World', 'welcometomyworld'],
                ['Tail Dragger', 'taildragger'],
                ['Street Dogs For Breakfast', 'streetdogsforbreakfast'],
            ],
            'Snake Oil King' => [
                ['Little By Little', 'littlebylittle'],
                ['We Walk Each Other Home', 'wewalkeachotherhome'],
                ['Tackle Box Hero', 'tackleboxhero'],
                ['Life as a Tree', 'lifeasatree'],
                ['Cosmic Confidante', 'cosmicconfidante'],
                ['Small Town', 'smalltown'],
            ],
            'Hailbound Queen' => [
                ['King Baby', 'kingbaby'],
                ['Blue Carousel', 'bluecarousel'],
                ['Keep Me In Your Heart', 'keepmeinyourheart'],
                ['Trashy', 'trashy'],
                ['Halloween Face', 'halloweenface'],
            ],
        ],
        'John Mayer' => [
            'Room for Squares' => [
                ['No Such Thing', 'nosuchthing'],
                ['Why Georgia', 'whygeorgia'],
                ['My Stupid Mouth', 'mystupidmouth'],
                ['Your Body Is a Wonderland', 'yourbodyisawonderland'],
                ['Neon', 'neon'],
                ['City Love', 'citylove'],
                ['83', 'track83'],
                ['3x5', 'track3x5'],
                ['Love Song for No One', 'lovesongfornoone'],
                ['Back to You', 'backtoyou'],
                ['Great Indoors', 'greatindoors'],
                ['Not Myself', 'notmyself'],
                ['St. Patrick\'s Day', 'stpatricksday'],
            ],
            'Heavier Things' => [
                ['Clarity', 'clarity'],
                ['Bigger Than My Body', 'biggerthanmybody'],
                ['Something\'s Missing', 'somethingsmissing'],
                ['New Deep', 'newdeep'],
                ['Come Back to Bed', 'comebacktobed'],
                ['Home Life', 'homelife'],
                ['Split Screen Sadness', 'splitscreensadness'],
                ['Daughters', 'daughters'],
                ['Only Heart', 'onlyheart'],
                ['Wheel', 'wheel'],
            ],
            'Continuum' => [
                ['Waiting on the World to Change', 'waitingontheworldtochange'],
                ['I Don\'t Trust Myself (With Loving You)', 'idonttrustmyselfwithlovingyou'],
                ['Belief', 'belief'],
                ['Gravity', 'gravity'],
                ['The Heart of Life', 'theheartoflife'],
                ['Vultures', 'vultures'],
                ['Stop This Train', 'stopthistrain'],
                ['Slow Dancing in a Burning Room', 'slowdancinginaburningroom'],
                ['Bold as Love', 'boldaslove'],
                ['Dreaming with a Broken Heart', 'dreamingwithabrokenheart'],
                ['In Repair', 'inrepair'],
                ['I\'m Gonna Find Another You', 'imgonnafindanotheryou'],
            ],
            'Battle Studies' => [
                ['Heartbreak Warfare', 'heartbreakwarfare'],
                ['All We Ever Do Is Say Goodbye', 'allweeverdoissaygoodbye'],
                ['Half of My Heart', 'halfofmyheart'],
                ['Who Says', 'whosays'],
                ['Perfectly Lonely', 'perfectlylonely'],
                ['Assassin', 'assassin'],
                ['Crossroads', 'crossroads'],
                ['War of My Life', 'warofmylife'],
                ['Edge of Desire', 'edgeofdesire'],
                ['Do You Know Me', 'doyouknowme'],
                ['Friends, Lovers or Nothing', 'friendsloversornothing'],
            ],
            'Paradise Valley' => [
                ['Wildfire', 'wildfire'],
                ['Dear Marie', 'dearmarie'],
                ['Waitin\' On The Day', 'waitinontheday'],
                ['Paper Doll', 'paperdoll'],
                ['Call Me The Breeze', 'callmethebreeze'],
                ['Who You Love (feat. Katy Perry)', 'whoyoulove'],
                ['I Will Be Found (Lost At Sea)', 'iwillbefoundlostatsea'],
                ['Wildfire (feat. Frank Ocean)', 'wildfirefeatfrankocean'],
                ['You\'re No One \'Til Someone Lets You Down', 'yourenoοnetilsomeονeletsyoudown'],
                ['Badge And Gun', 'badgeandgun'],
                ['On The Way Home', 'onthewayhome'],
            ],
            'The Search for Everything' => [
                ['Still Feel Like Your Man', 'stillfeellikeyourman'],
                ['Emoji of a Wave', 'emojiofawave'],
                ['Helpless', 'helpless'],
                ['Love On the Weekend', 'loveontheweekend'],
                ['In the Blood', 'intheblood'],
                ['Changing', 'changing'],
                ['Theme from \'The Search for Everything\'', 'themefromthesearchforeverything'],
                ['Moving On and Getting Over', 'movingonandgettingover'],
                ['Never On the Day You Leave', 'neveronthedayyοuleave'],
                ['Rosie', 'rosie'],
                ['Roll It On Home', 'rollitonhome'],
                ['You\'re Gonna Live Forever in Me', 'youregonnaliveforeverinme'],
            ],
            'Sob Rock' => [
                ['Last Train Home', 'lasttrainhome'],
                ['Shouldn\'t Matter but It Does', 'shouldntmatterbutitdoes'],
                ['New Light', 'newlight'],
                ['Why You No Love Me', 'whyyounoloveme'],
                ['Wild Blue', 'wildblue'],
                ['Shot in the Dark', 'shotinthedark'],
                ['I Guess I Just Feel Like', 'iguessiјustfeellike'],
                ['Til the Right One Comes', 'tiltherightοnecomes'],
                ['Carry Me Away', 'carrymeaway'],
                ['All I Want Is to Be With You', 'alliwantistobewithyou'],
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
                    $albumId = $this->findAlbumCategory((string) $albumName, $artistId);
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
                'AddTracksGroup2 completed. Added %d tracks for %d artists.',
                $totalTracks,
                count(self::TRACKS)
            ));
        } catch (\Exception $e) {
            $this->logger->error('Failed to add tracks (Group 2): ' . $e->getMessage());
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

        // Create new category - Magento will calculate path from parent_id

        // Create new category
        $category = $this->categoryFactory->create();
        $category->setName($name);
        $category->setUrlKey($urlKey);
        $category->setParentId($parentId);
        $category->setIsActive(true);
        $category->setIncludeInMenu(true);
        $category->setPosition($position);

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
