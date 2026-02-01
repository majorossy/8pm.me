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
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Create Category Structure Data Patch
 *
 * Creates the complete category hierarchy for the 8pm.me Music Platform:
 *   Root Catalog (1) → Default Category (2) → Artists (48)
 *     → Artist categories (7 artists)
 *       → Album categories (35 albums)
 *         → Track categories (403 tracks)
 *
 * This patch is idempotent - it checks for existing categories by url_key
 * before creating new ones.
 */
class CreateCategoryStructure implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * Complete category structure data extracted from database.
     * Format: [name, url_key, children[]]
     */
    private const CATEGORY_STRUCTURE = [
        // Artists (level 3)
        'STS9' => [
            'url_key' => 'sts9',
            'albums' => [
                'Interplanetary Escape Vehicle' => [
                    'url_key' => 'interplanetaryescapevehicle',
                    'tracks' => [
                        ['Moon Socket', 'moonsocket'],
                        ['Hubble', 'hubble'],
                        ['Wika Chikana', 'wikachikana'],
                        ['H B Walks To School', 'hbwalkstoschool'],
                        ['Four Year Puma', 'fouryearpuma'],
                        ['Tap-In', 'tapin'],
                        ['Quests', 'quests'],
                        ['Evasive Manuvers', 'evasivemanuvers'],
                    ],
                ],
                'Offered Schematics Suggesting Peace' => [
                    'url_key' => 'offeredschematicssuggestingpeace',
                    'tracks' => [
                        ['Forward', 'forward'],
                        ['Squares And Cubes', 'squaresandcubes'],
                        ['Otherwise Formless', 'otherwiseformless'],
                        ['Kamuy', 'kamuy'],
                        ['Water Song', 'watersong'],
                        ['Common Objects Strangely Placed', 'commonobjectsstrangelyplaced'],
                        ['And Some Are Angels', 'andsomeareangels'],
                        ['Turtle', 'turtle'],
                        ['Mischief Of The Sleepwalker', 'mischiefofthesleepwalker'],
                        ['Inspire Strikes Back', 'inspirestrikesback'],
                        ['Eb', 'eb'],
                    ],
                ],
                'Artifact' => [
                    'url_key' => 'artifact',
                    'tracks' => [
                        ['Musical Story, Yes', 'musicalstoryyes'],
                        ['Better Day', 'betterday'],
                        ['By The Morning Sun', 'bythemorningsun'],
                        ['Tokyo', 'tokyo'],
                        ['Artifact', 'artifact88970'],
                        ['Native End', 'nativeend'],
                        ['ReEmergence', 'reemergence'],
                        ['Peoples', 'peoples'],
                        ['GLOgli', 'glogli'],
                        ['Today', 'today'],
                        ['Tonight The Ocean Swallowed The Moon', 'tonighttheoceanswallowedthemoon'],
                        ['Forest Hu', 'foresthu'],
                        ['Somesing', 'somesing'],
                        ['Trinocular', 'trinocular'],
                        ['Vibyl', 'vibyl'],
                        ['8 & A Extra', '8aextra'],
                        ['Possibilities', 'possibilities'],
                        ['Peoples, Pt. 2', 'peoplespt2'],
                        ['First Mist Over Clear Lake', 'firstmistoverclearlake'],
                        ['Music, Us', 'musicus'],
                    ],
                ],
                'Peaceblaster' => [
                    'url_key' => 'peaceblaster',
                    'tracks' => [
                        ['peaceblaster68', 'peaceblaster68'],
                        ['peaceblaster08', 'peaceblaster08'],
                        ['Metameme', 'metameme'],
                    ],
                ],
            ],
        ],
        'The Disco Biscuits' => [
            'url_key' => 'thediscobiscuits',
            'albums' => [
                'Encephalous Crime' => [
                    'url_key' => 'encephalouscrime',
                    'tracks' => [
                        ['Mr. Don', 'mrdon'],
                        ['Rainbow Song', 'rainbowsong'],
                        ['Stone', 'stone'],
                        ['The Devil\'s Waltz', 'thedevilswaltz'],
                        ['El Camino del Gordissimo', 'elcaminodelgordissimo'],
                        ['Radiator', 'radiator'],
                        ['Trooper McCue', 'troopermccue'],
                        ['Pat and Dex', 'patanddex'],
                        ['Barfly', 'barfly'],
                        ['Pygmy Twylyte', 'pygmytwylyte'],
                        ['Basis for a Day', 'basisforaday'],
                    ],
                ],
                'Uncivilized Area' => [
                    'url_key' => 'uncivilizedarea',
                    'tracks' => [
                        ['Vassillios', 'vassillios'],
                        ['Aceetobee', 'aceetobee'],
                        ['Jamillia', 'jamillia'],
                        ['Little Betty Boop', 'littlebettyboop'],
                        ['MEMPHIS', 'memphis'],
                        ['Morph Dusseldorf', 'morphdusseldorf'],
                        ['I-Man', 'iman'],
                        ['AWOL\'s Blues', 'awolsblues'],
                    ],
                ],
                'They Missed the Perfume' => [
                    'url_key' => 'theymissedtheperfume',
                    'tracks' => [
                        ['Highwire', 'highwire'],
                        ['Spacebirdmatingcall', 'spacebirdmatingcall'],
                        ['Haleakala Crater', 'haleakalacrater'],
                        ['Home Again', 'homeagain'],
                        ['Mindless Dribble', 'mindlessdribble'],
                        ['I Remember When', 'irememberwhen'],
                    ],
                ],
                'Senor Boombox' => [
                    'url_key' => 'senorboombox',
                    'tracks' => [
                        ['Hope', 'hope'],
                        ['Float Like a Butterfly', 'floatlikeabutterfly'],
                        ['In the Sky', 'inthesky'],
                        ['Floodlights', 'floodlights'],
                        ['Jigsaw Earth', 'jigsawearth'],
                        ['Sugarcane', 'sugarcane'],
                        ['Sound One', 'soundone'],
                        ['The Tunnel', 'thetunnel'],
                        ['Sprawl', 'sprawl'],
                        ['Floes', 'floes'],
                        ['Triumph', 'triumph'],
                        ['Hope II', 'hopeii'],
                        ['Hope III', 'hopeiii'],
                    ],
                ],
                'The Wind at Four to Fly' => [
                    'url_key' => 'thewindatfourtofly',
                    'tracks' => [
                        ['World Is Spinning', 'worldisspinning'],
                        ['Voices Insane', 'voicesinsane'],
                        ['Caterpillar', 'caterpillar'],
                        ['Kitchen Mitts', 'kitchenmitts'],
                        ['Sweating Bullets', 'sweatingbullets'],
                        ['Wet', 'wet'],
                        ['Spy', 'spy'],
                        ['Morph Dusseldorf', 'morphdusseldorf21308'],
                        ['Story of the World', 'storyoftheworld'],
                        ['Basis for a Day', 'basisforaday11749'],
                        ['Little Shimmy in a Conga Line', 'littleshimmyinacongaline'],
                        ['Pat and Dex', 'patanddex48391'],
                    ],
                ],
            ],
        ],
        'Railroad Earth' => [
            'url_key' => 'railroadearth',
            'albums' => [
                'The Black Bear Sessions' => [
                    'url_key' => 'theblackbearsessions',
                    'tracks' => [
                        ['Head', 'head'],
                        ['Lordy Lordy', 'lordylordy'],
                        ['Seven Story Mountain', 'sevenstorymountain'],
                        ['Chains', 'chains'],
                        ['Black Bear', 'blackbear'],
                        ['Colorado', 'colorado'],
                        ['Real Love', 'reallove'],
                        ['Stillwater Getaway', 'stillwatergetaway'],
                        ['Cold Water', 'coldwater'],
                        ['Railroad Earth', 'railroadearth94309'],
                    ],
                ],
                'Bird in a House' => [
                    'url_key' => 'birdinahouse',
                    'tracks' => [
                        ['Drag Him Down', 'draghimdown'],
                        ['Bird in a House', 'birdinahouse80993'],
                        ['Like a Buddha', 'likeabuddha'],
                        ['Pack a Day', 'packaday'],
                        ['Mountain Time', 'mountaintime'],
                        ['Give that Boy a Hand', 'givethatboyahand'],
                        ['Peace on Earth', 'peaceonearth'],
                        ['Walk on By', 'walkonby'],
                        ['Mighty River', 'mightyriver'],
                        ['Lois Ann', 'loisann'],
                        ['Came up Smilin\'', 'cameupsmilin'],
                        ['Dandelion Wine', 'dandelionwine'],
                        ['Saddle of the Sun', 'saddleofthesun'],
                    ],
                ],
                'The Good Life' => [
                    'url_key' => 'thegoodlife',
                    'tracks' => [
                        ['Storms', 'storms'],
                        ['Bread and Water', 'breadandwater'],
                        ['Mourning Flies', 'mourningflies'],
                        ['Long Way Go To', 'longwaygoto'],
                        ['The Good Life', 'thegoodlife53316'],
                        ['In the Basement', 'inthebasement'],
                        ['Water Fountain Quicksand', 'waterfountainquicksand'],
                        ['Goat', 'goat'],
                        ['Said What You Mean', 'saidwhatyoumean'],
                        ['Way of the Buffalo', 'wayofthebuffalo'],
                        ['Neath the Stars', 'neaththestars'],
                    ],
                ],
                'Elko' => [
                    'url_key' => 'elko',
                    'tracks' => [
                        ['Long Way To Go', 'longwaytogo'],
                        ['Colorado', 'colorado63165'],
                        ['Bird In A House', 'birdinahouse62719'],
                        ['The Hunting Song', 'thehuntingsong'],
                        ['Old Man And The Land', 'oldmanandtheland'],
                        ['Head', 'head84169'],
                        ['Elko', 'elko65636'],
                        ['Mighty River', 'mightyriver56664'],
                        ['Like A Buddha', 'likeabuddha93308'],
                        ['Warhead Boogie', 'warheadboogie'],
                        ['Railroad Earth', 'railroadearth54826'],
                        ['Seven Story Mountain', 'sevenstorymountain39279'],
                    ],
                ],
                'Amen Corner' => [
                    'url_key' => 'amencorner',
                    'tracks' => [
                        ['Been Down This Road', 'beendownthisroad'],
                        ['Hard Livin\'', 'hardlivin'],
                        ['Bringin\' My Baby Back Home', 'bringinmybabybackhome'],
                        ['The Forecast', 'theforecast'],
                        ['Right In Tune', 'rightintune'],
                        ['Waggin\' The Dog', 'wagginthedog'],
                        ['Little Bit O\' Me', 'littlebitome'],
                        ['Lonecroft Ramble', 'lonecroftramble'],
                        ['Crossing The Gap', 'crossingthegap'],
                        ['All Alone', 'allalone'],
                        ['You Never Know', 'youneverknow'],
                        ['Lovin\' You', 'lovinyou'],
                    ],
                ],
                'Railroad Earth Album' => [
                    'url_key' => 'railroadearthalbum',
                    'tracks' => [
                        ['Long Walk Home', 'longwalkhome'],
                        ['The Jupiter & The 119', 'thejupiterthe119'],
                        ['Black Elk Speaks', 'blackelkspeaks'],
                        ['Day On The Sand', 'dayonthesand'],
                        ['Lone Croft Farewell', 'lonecroftfarewell'],
                        ['Too Much Information', 'toomuchinformation'],
                        ['Spring-Heeled Jack', 'springheeledjack'],
                        ['On The Banks', 'onthebanks'],
                        ['Potter\'s Field', 'pottersfield'],
                    ],
                ],
                'Captain Nowhere' => [
                    'url_key' => 'captainnowhere',
                    'tracks' => [
                        ['Blazin\' A Trail', 'blazinatrail'],
                        ['Only By The Light', 'onlybythelight'],
                        ['Adding My Voice', 'addingmyvoice'],
                        ['The Berkeley Flash', 'theberkeleyflash'],
                        ['Ravens Child', 'ravenschild'],
                        ['Captain Nowhere', 'captainnowhere40075'],
                    ],
                ],
            ],
        ],
        'The String Cheese Incident' => [
            'url_key' => 'thestringcheeseincident',
            'albums' => [
                'Born On The Wrong Planet' => [
                    'url_key' => 'bornonthewrongplanet',
                    'tracks' => [
                        ['Black Clouds', 'blackclouds'],
                        ['Born On The Wrong Planet', 'bornonthewrongplanet25393'],
                        ['Land\'s End', 'landsend'],
                        ['The Remington Ride', 'theremingtonride'],
                        ['Resume Man', 'resumeman'],
                        ['Elvis Wild Ride', 'elviswildride'],
                        ['Bigger Isn\'t Better', 'biggerisntbetter'],
                        ['Johnny Cash', 'johnnycash'],
                        ['Lester Had A Coconut', 'lesterhadacoconut'],
                        ['Diggin\' In', 'digginin'],
                        ['Texas', 'texas'],
                        ['Jellyfish', 'jellyfish'],
                    ],
                ],
                'A String Cheese Incident' => [
                    'url_key' => 'astringcheeseincident',
                    'tracks' => [
                        ['Lonesome Fiddle Blues', 'lonesomefiddleblues'],
                        ['Little Hands', 'littlehands'],
                        ['Dudley\'s Kitchen', 'dudleyskitchen'],
                        ['Rhythm Of The Road', 'rhythmoftheroad'],
                        ['How Mountain Girls Can Love', 'howmountaingirlscanlove'],
                        ['Pirates', 'pirates'],
                        ['Wake Up', 'wakeup'],
                        ['Land\'s End', 'landsend59460'],
                        ['San Jose', 'sanjos'],
                        ['Walk This Way', 'walkthisway'],
                    ],
                ],
                'Round The Wheel' => [
                    'url_key' => 'roundthewheel',
                    'tracks' => [
                        ['Samba DeGreeley', 'sambadegreeley'],
                        ['Come As You Are', 'comeasyouare'],
                        ['Restless Wind', 'restlesswind'],
                        ['On The Road', 'ontheroad'],
                        ['Road Home', 'roadhome'],
                        ['Galactic', 'galactic'],
                        ['100 Year Flood', '100yearflood'],
                        ['MLT', 'mlt'],
                        ['Got What He Wanted', 'gotwhathewanted'],
                        ['\'Round The Wheel', 'roundthewheel52539'],
                        ['Good Times Around The Bend', 'goodtimesaroundthebend'],
                    ],
                ],
                'Carnival \'99' => [
                    'url_key' => 'carnival99',
                    'tracks' => [
                        ['Shenandoah Breakdown', 'shenandoahbreakdown'],
                        ['Missin\' Me', 'missinme'],
                        ['Mouna Bowa', 'mounabowa'],
                        ['Bar Stool', 'barstool'],
                        ['Take Five', 'takefive'],
                        ['Hey Pocky Way', 'heypockyway'],
                        ['Black Clouds', 'blackclouds70723'],
                        ['Lesters Rant', 'lestersrant'],
                        ['Footprints', 'footprints'],
                        ['Don\'t Say', 'dontsay'],
                        ['Birdland', 'birdland'],
                        ['Hold Whatcha Got', 'holdwhatchagot'],
                        ['Jellyfish', 'jellyfish46930'],
                        ['Drum Jam', 'drumjam'],
                        ['Texas', 'texas14345'],
                    ],
                ],
                'Outside Inside' => [
                    'url_key' => 'outsideinside',
                    'tracks' => [
                        ['Outside Inside', 'outsideinside31911'],
                        ['Joyful Sound', 'joyfulsound'],
                        ['Close Your Eyes', 'closeyoureyes'],
                        ['Search', 'search'],
                        ['Drifting', 'drifting'],
                        ['Black And White', 'blackandwhite'],
                        ['Lost', 'lost'],
                        ['Latinissmo', 'latinissmo'],
                        ['Sing A New Song', 'singanewsong'],
                        ['Rollover', 'rollover'],
                        ['Up The Canyon', 'upthecanyon'],
                    ],
                ],
                'Untying The Not' => [
                    'url_key' => 'untyingthenot',
                    'tracks' => [
                        ['Wake Up', 'wakeup35704'],
                        ['Sirens', 'sirens'],
                        ['Looking Glass', 'lookingglass'],
                        ['Orions Belt', 'orionsbelt'],
                        ['Mountain Girl', 'mountaingirl'],
                        ['Lonesome Road Blues', 'lonesomeroadblues'],
                        ['Elijah', 'elijah'],
                        ['Valley Of The Jig', 'valleyofthejig'],
                        ['Tinder Box', 'tinderbox'],
                        ['Just Passin\' Through', 'justpassinthrough'],
                        ['Who Am I', 'whoami'],
                        ['Time Alive', 'timealive'],
                        ['On My Way', 'onmyway'],
                    ],
                ],
                'One Step Closer' => [
                    'url_key' => 'onestepcloser',
                    'tracks' => [
                        ['Give Me The Love', 'givemethelove'],
                        ['Sometimes A River', 'sometimesariver'],
                        ['Big Compromise', 'bigcompromise'],
                        ['Until The Music\'s Over', 'untilthemusicsover'],
                        ['Silence In Your Head', 'silenceinyourhead'],
                        ['Farther', 'farther'],
                        ['Drive', 'drive'],
                        ['Betray The Dark', 'betraythedark'],
                        ['45th of November', '45thofnovember'],
                        ['One Step Closer', 'onestepcloser88021'],
                        ['Rainbow Serpent', 'rainbowserpent'],
                        ['Swampy Waters', 'swampywaters'],
                        ['Brand New Start', 'brandnewstart'],
                    ],
                ],
                'Trick Or Treat' => [
                    'url_key' => 'trickortreat',
                    'tracks' => [
                        ['Land\'s End', 'landsend53564'],
                        ['Walking On The Moon', 'walkingonthemoon'],
                        ['Come Together', 'cometogether'],
                        ['The Wedge', 'thewedge'],
                        ['Get Down Tonight', 'getdowntonight'],
                        ['Being For The Benefit Of Mr. Kite!', 'beingforthebenefitofmrkite'],
                        ['Hot In Herre', 'hotinherre'],
                        ['War Pigs', 'warpigs'],
                        ['Freeker By The Speaker', 'freekerbythespeaker'],
                        ['Exodus', 'exodus'],
                        ['Under African Skies', 'underafricanskies'],
                        ['Tightrope', 'tightrope'],
                        ['L.A. Woman', 'lawoman'],
                        ['Peace Train', 'peacetrain'],
                        ['Rock The Casbah', 'rockthecasbah'],
                        ['So What', 'sowhat'],
                        ['\'Round The Wheel', 'roundthewheel76917'],
                        ['Restless Wind', 'restlesswind66522'],
                    ],
                ],
                'Song In My Head' => [
                    'url_key' => 'songinmyhead',
                    'tracks' => [
                        ['Colorado Bluebird Sky', 'coloradobluebirdsky'],
                        ['Betray The Dark', 'betraythedark97612'],
                        ['Let\'s Go Outside', 'letsgooutside'],
                        ['Song In My Head', 'songinmyhead34536'],
                        ['Struggling Angel', 'strugglingangel'],
                        ['Can\'t Wait Another Day', 'cantwaitanotherday'],
                        ['So Far From Home', 'sofarfromhome'],
                        ['Rosie', 'rosie'],
                        ['Stay Through', 'staythrough'],
                        ['Colliding', 'colliding'],
                    ],
                ],
                'Believe' => [
                    'url_key' => 'believe',
                    'tracks' => [
                        ['Believe', 'believe33779'],
                        ['Sweet Spot', 'sweetspot'],
                        ['My One and Only', 'myoneandonly'],
                        ['Down a River', 'downariver'],
                        ['Get Tight', 'gettight'],
                        ['Stop Drop Roll', 'stopdroproll'],
                        ['Flying', 'flying'],
                        ['So Much Fun', 'somuchfun'],
                        ['Beautiful', 'beautiful'],
                    ],
                ],
            ],
        ],
        'Grace Potter and the Nocturnals' => [
            'url_key' => 'gracepotterandthenocturnals',
            'albums' => [
                'Original Soul' => [
                    'url_key' => 'originalsoul',
                    'tracks' => [
                        ['At Your Request', 'atyourrequest'],
                        ['Go Down Low', 'godownlow'],
                        ['Crazy Parade', 'crazyparade'],
                        ['I Chose You', 'ichoseyou'],
                        ['Deliverance Road', 'deliveranceroad'],
                        ['Gumbo Moon', 'gumbomoon'],
                        ['Hidden Superstition', 'hiddensuperstition'],
                        ['Moonbeams', 'moonbeams'],
                        ['No Good, Mean Old, Lowdown Lover Man', 'nogoodmeanoldlowdownloverman'],
                        ['Somebody Fix Me', 'somebodyfixme'],
                        ['Driving Blind', 'drivingblind'],
                        ['Bull in a China Shop', 'bullinachinashop'],
                        ['Kissing in a Tree', 'kissinginatree'],
                    ],
                ],
                'Midnight' => [
                    'url_key' => 'midnight',
                    'tracks' => [
                        ['Hot to the Touch', 'hottothetouch'],
                        ['Alive Tonight', 'alivetonight'],
                        ['Your Girl', 'yourgirl'],
                        ['Empty Heart', 'emptyheart'],
                        ['The Miner', 'theminer'],
                        ['Delirious', 'delirious'],
                        ['Look What We\'ve Become', 'lookwhatwevebecome'],
                        ['Instigators', 'instigators'],
                        ['Biggest Fan', 'biggestfan'],
                        ['Low', 'low'],
                        ['Nobody\'s Born with a Broken Heart', 'nobodysbornwithabrokenheart'],
                        ['Let You Go', 'letyougo'],
                    ],
                ],
            ],
        ],
        'Tea Leaf Green' => [
            'url_key' => 'tealeafgreen',
            'albums' => [
                'Tea Leaf Green Album' => [
                    'url_key' => 'tealeafgreenalbum',
                    'tracks' => [
                        ['Steal Your Imagination', 'stealyourimagination'],
                        ['Cherry Red Guitar', 'cherryredguitar'],
                        ['Apocalyptic Cowboy', 'apocalypticcowboy'],
                        ['California', 'california'],
                        ['Crackers and Cheese', 'crackersandcheese'],
                        ['Professor\'s Blues', 'professorsblues'],
                        ['New Year\'s Eve', 'newyearseve'],
                        ['Ocean View', 'oceanview'],
                        ['Boomtown', 'boomtown'],
                        ['Passion', 'passion'],
                        ['Asphalt Funk', 'asphaltfunk'],
                        ['Turn the Page', 'turnthepage'],
                    ],
                ],
                'Taught to Be Proud' => [
                    'url_key' => 'taughttobeproud',
                    'tracks' => [
                        ['The Garden', 'thegarden'],
                        ['Taught to Be Proud', 'taughttobeproud84760'],
                        ['Rapture', 'rapture'],
                        ['If It Wasn\'t for the Money', 'ifitwasntforthemoney'],
                        ['I\'ve Been Seeking', 'ivebeenseeking'],
                        ['John Brown', 'johnbrown'],
                        ['Pretty Jane', 'prettyjane'],
                        ['5000 Acres', '5000acres'],
                        ['Morning Sun', 'morningsun'],
                        ['Ride Together', 'ridetogether'],
                        ['Flippin\' the Bird', 'flippinthebird'],
                    ],
                ],
                'Raise Up the Tent' => [
                    'url_key' => 'raiseupthetent',
                    'tracks' => [
                        ['Let Us Go', 'letusgo'],
                        ['Don\'t Curse at the Night', 'dontcurseatthenight'],
                        ['Red Ribbons', 'redribbons'],
                        ['I\'ve Got a Truck', 'ivegotatruck'],
                        ['Innocence', 'innocence'],
                        ['Not Fit', 'notfit'],
                        ['Borrowed Time', 'borrowedtime'],
                        ['Slept Through Sunday', 'sleptthroughsunday'],
                        ['Standing Still', 'standingstill'],
                        ['Stick to the Shallows', 'sticktotheshallows'],
                        ['Keeping the Faith', 'keepingthefaith'],
                    ],
                ],
            ],
        ],
        'Of a Revolution' => [
            'url_key' => 'ofarevolution',
            'albums' => [
                'The Wanderer' => [
                    'url_key' => 'thewanderer',
                    'tracks' => [
                        ['Missing Pieces', 'missingpieces'],
                        ['That Was a Crazy Game of Poker', 'thatwasacrazygameofpoker'],
                        ['Black Rock', 'blackrock'],
                        ['Conquering Fools', 'conqueringfools'],
                        ['Get Away', 'getaway'],
                        ['About an Hour Ago', 'aboutanhourago'],
                        ['Toy Store', 'toystore'],
                        ['About Mr. Brown', 'aboutmrbrown'],
                        ['Ladanday', 'ladanday'],
                    ],
                ],
                'Soul\'s Aflame' => [
                    'url_key' => 'soulsaflame',
                    'tracks' => [
                        ['City on Down', 'cityondown'],
                        ['Untitled', 'untitled'],
                        ['So Moved On', 'somovedon'],
                        ['Night Shift', 'nightshift'],
                        ['Ran Away to the Top of the World Today', 'ranawaytothetopoftheworldtoday'],
                        ['On Top the Cage', 'ontopthecage'],
                        ['The Wanderer', 'thewanderer90847'],
                        ['When Can I Go Home?', 'whencanigohome'],
                        ['To Zion Goes I', 'toziongoesi'],
                        ['Hey Girl', 'heygirl'],
                        ['I Feel Home', 'ifeelhome'],
                    ],
                ],
                'Risen' => [
                    'url_key' => 'risen',
                    'tracks' => [
                        ['Hey Girl', 'heygirl80214'],
                        ['Delicate Few', 'delicatefew'],
                        ['Hold on True', 'holdontrue'],
                        ['If Only She Knew', 'ifonlysheknew'],
                        ['Untitled', 'untitled81804'],
                        ['She Gone', 'shegone'],
                        ['King of the Thing', 'kingofthething'],
                        ['Night Shift', 'nightshift34560'],
                        ['About Mr. Brown', 'aboutmrbrown66679'],
                        ['Someone in the Road', 'someoneintheroad'],
                        ['Here\'s to You', 'herestoyou'],
                    ],
                ],
                'In Between Now and Then' => [
                    'url_key' => 'inbetweennowandthen',
                    'tracks' => [
                        ['Now', 'now'],
                        ['Dareh Meyod', 'darehmeyod'],
                        ['Risen', 'risen20588'],
                        ['Right on Time', 'rightontime'],
                        ['Mr. Moon', 'mrmoon'],
                        ['Revisited', 'revisited'],
                        ['Hey Girl', 'heygirl90331'],
                        ['James', 'james'],
                        ['Coalminer', 'coalminer'],
                        ['Old Man Time', 'oldmantime'],
                        ['Anyway', 'anyway'],
                        ['Road Outside Columbus', 'roadoutsidecolumbus'],
                        ['Any Time Now', 'anytimenow'],
                        ['Whose Chariot?', 'whosechariot'],
                        ['Then', 'then'],
                    ],
                ],
            ],
        ],
    ];

    private ModuleDataSetupInterface $moduleDataSetup;
    private CategoryFactory $categoryFactory;
    private CategoryRepositoryInterface $categoryRepository;
    private CollectionFactory $categoryCollectionFactory;
    private StoreManagerInterface $storeManager;
    private LoggerInterface $logger;

    /** @var array<string, int> Cache of url_key => category_id */
    private array $urlKeyCache = [];

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CategoryFactory $categoryFactory,
        CategoryRepositoryInterface $categoryRepository,
        CollectionFactory $categoryCollectionFactory,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->categoryFactory = $categoryFactory;
        $this->categoryRepository = $categoryRepository;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->storeManager = $storeManager;
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

            // Ensure Artists root category exists
            $artistsRootId = $this->ensureArtistsRoot();

            // Create all artists, albums, and tracks
            $artistPosition = 1;
            foreach (self::CATEGORY_STRUCTURE as $artistName => $artistData) {
                $artistId = $this->createCategoryIfNotExists(
                    $artistName,
                    $artistData['url_key'],
                    $artistsRootId,
                    $artistPosition++,
                    ['is_artist' => 1, 'is_album' => 0, 'is_song' => 0]
                );

                $albumPosition = 1;
                foreach ($artistData['albums'] as $albumName => $albumData) {
                    $albumId = $this->createCategoryIfNotExists(
                        $albumName,
                        $albumData['url_key'],
                        $artistId,
                        $albumPosition++,
                        ['is_artist' => 0, 'is_album' => 1, 'is_song' => 0]
                    );

                    $trackPosition = 1;
                    foreach ($albumData['tracks'] as $trackData) {
                        [$trackName, $trackUrlKey] = $trackData;
                        $this->createCategoryIfNotExists(
                            $trackName,
                            $trackUrlKey,
                            $albumId,
                            $trackPosition,
                            [
                                'is_artist' => 0,
                                'is_album' => 0,
                                'is_song' => 1,
                                'song_track_number' => $trackPosition
                            ]
                        );
                        $trackPosition++;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to create category structure: ' . $e->getMessage());
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

        // Get the url_key attribute ID
        $attributeId = $connection->fetchOne(
            "SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'url_key' AND entity_type_id = 3"
        );

        if (!$attributeId) {
            return;
        }

        // Fetch all url_keys
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
     * Ensure the Artists root category (ID 48) exists
     */
    private function ensureArtistsRoot(): int
    {
        // Check if Artists category exists by url_key
        if (isset($this->urlKeyCache['artists'])) {
            return $this->urlKeyCache['artists'];
        }

        // Check if category with expected name exists under Default Category (2)
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToFilter('name', 'Artists');
        $collection->addFieldToFilter('parent_id', 2);
        $collection->setPageSize(1);

        if ($collection->getSize() > 0) {
            $category = $collection->getFirstItem();
            return (int) $category->getId();
        }

        // Create Artists category
        $category = $this->categoryFactory->create();
        $category->setName('Artists');
        $category->setUrlKey('artists');
        $category->setParentId(2);
        $category->setIsActive(true);
        $category->setIncludeInMenu(true);
        $category->setPath('1/2');

        $this->categoryRepository->save($category);
        $this->logger->info(sprintf('Created Artists root category (ID: %d)', $category->getId()));

        return (int) $category->getId();
    }

    /**
     * Create a category if it doesn't exist (check by url_key)
     *
     * @param string $name Category name
     * @param string $urlKey URL key for idempotency check
     * @param int $parentId Parent category ID
     * @param int $position Sort position
     * @param array<string, mixed> $attributes Custom attributes to set
     * @return int Category ID
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

            // Still update the attributes on existing category
            $this->updateCategoryAttributes($categoryId, $attributes);

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
     * Update attributes on an existing category
     */
    private function updateCategoryAttributes(int $categoryId, array $attributes): void
    {
        if (empty($attributes)) {
            return;
        }

        try {
            $category = $this->categoryRepository->get($categoryId);
            foreach ($attributes as $key => $value) {
                $category->setData($key, $value);
            }
            $this->categoryRepository->save($category);
        } catch (\Exception $e) {
            $this->logger->warning(sprintf(
                'Failed to update attributes for category ID %d: %s',
                $categoryId,
                $e->getMessage()
            ));
        }
    }

    /**
     * @inheritDoc
     */
    public function revert(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        // Note: Reverting will delete all categories under Artists root
        // This is destructive and should be used with caution
        $this->logger->warning('Category structure revert requested - this will delete all artist/album/track categories');

        // Find Artists root
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToFilter('url_key', 'artists');
        $collection->addFieldToFilter('parent_id', 2);
        $collection->setPageSize(1);

        if ($collection->getSize() === 0) {
            $this->logger->info('Artists root category not found - nothing to revert');
            $this->moduleDataSetup->getConnection()->endSetup();
            return;
        }

        $artistsRoot = $collection->getFirstItem();
        $artistsPath = $artistsRoot->getPath() . '/%';

        // Delete all child categories (tracks, albums, artists) in reverse order
        $childCollection = $this->categoryCollectionFactory->create();
        $childCollection->addFieldToFilter('path', ['like' => $artistsPath]);
        $childCollection->setOrder('level', 'DESC');

        foreach ($childCollection as $category) {
            try {
                $this->categoryRepository->delete($category);
                $this->logger->info(sprintf('Deleted category "%s" (ID: %d)', $category->getName(), $category->getId()));
            } catch (\Exception $e) {
                $this->logger->error(sprintf(
                    'Failed to delete category "%s" (ID: %d): %s',
                    $category->getName(),
                    $category->getId(),
                    $e->getMessage()
                ));
            }
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies(): array
    {
        return [
            CreateCategoryAttributes::class
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
