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
 * Add Tracks Group 3 Data Patch
 *
 * Adds tracks for King Gizzard & The Lizard Wizard, moe., Guster, and Ween albums.
 */
class AddTracksGroup3 implements DataPatchInterface
{
    /**
     * Track data structure
     * Format: Artist Name => Album Name => [[track_name, track_url_key], ...]
     */
    private const TRACKS = [
        'King Gizzard & The Lizard Wizard' => [
            '12 Bar Bruise' => [
                ['Elbow', 'elbow'],
                ['Muckraker', 'muckraker'],
                ['Nein', 'nein'],
                ['12 Bar Bruise', '12barbruise'],
                ['Garage Liddiard', 'garageliddiard'],
                ['Sam Cherry\'s Last Shot', 'samcherryslastshot'],
                ['High Hopes Low', 'highhopeslow'],
                ['Cut Throat Boogie', 'cutthroatboogie'],
                ['Bloody Ripper', 'bloodyripper'],
                ['Uh Oh, I Called Mum', 'uhohicalledmum'],
                ['Sea Of Trees', 'seaoftrees'],
                ['Footy Footy', 'footyfooty'],
            ],
            'Eyes Like the Sky' => [
                ['Eyes Like The Sky', 'eyeslikethesky'],
                ['Year Of Our Lord', 'yearofourlord'],
                ['The Raid', 'theraid'],
                ['Drum Run', 'drumrun'],
                ['Evil Man', 'evilman'],
                ['Fort Whipple', 'fortwhipple'],
                ['The God Mans Goat Lust', 'thegodmansgoatlust'],
                ['The Killing Ground', 'thekillingground'],
                ['Dust In The Wind', 'dustinthewind'],
                ['Guns & Horses', 'gunshorses'],
            ],
            'Float Along – Fill Your Lungs' => [
                ['Head On/Pill', 'headonpill'],
                ['I Am Not A Man Unless I Have A Woman', 'iamnotamanunlessihaveawoman'],
                ['God Is Calling Me Back Home', 'godiscallingmebackhome'],
                ['30 Past 7', '30past7'],
                ['Let Me Mend The Past', 'letmemendthepast'],
                ['Mystery Jack', 'mysteryjack'],
                ['Pop In My Step', 'popinmystep'],
                ['Float Along - Fill Your Lungs', 'floatalongfillyourlungs'],
            ],
            'Oddments' => [
                ['Alluda Majaka', 'alludamajaka'],
                ['Stressin\'', 'stressin'],
                ['Vegemite', 'vegemite'],
                ['It\'s Got Old', 'itsgotold'],
                ['Work This Time', 'workthistime'],
                ['ABABCD', 'ababcd'],
                ['Sleepwalker', 'sleepwalker'],
                ['Hot Wax', 'hotwax'],
                ['Crying', 'crying'],
                ['Pipe-Dream', 'pipedream'],
                ['Homeless Man In Adidas', 'homelessmaninadidas'],
                ['Oddments', 'oddments'],
            ],
            'I\'m in Your Mind Fuzz' => [
                ['I\'m In Your Mind', 'iminyourmind'],
                ['I\'m Not In Your Mind', 'imnotinyourmind'],
                ['Cellophane', 'cellophane'],
                ['I\'m In Your Mind Fuzz', 'iminyourmindfuzz'],
                ['Empty', 'empty'],
                ['Hot Water', 'hotwater'],
                ['Am I In Heaven?', 'amiinheaven'],
                ['Slow Jam 1', 'slowjam1'],
                ['Satan Speeds Up', 'satanspeedsup'],
                ['Her And I (Slow Jam 2)', 'herandislowjam2'],
            ],
            'Quarters!' => [
                ['The River', 'theriver'],
                ['Infinite Rise', 'infiniterise'],
                ['God Is In The Rhythm', 'godisinrhythm'],
                ['Lonely Steel Sheet Flyer', 'lonelysteelsheetflyer'],
            ],
            'Paper Mâché Dream Balloon' => [
                ['Sense', 'sense'],
                ['Bone', 'bone'],
                ['Dirt', 'dirt'],
                ['Paper Mâché Dream Balloon', 'papermachedreamballoon'],
                ['Trapdoor', 'trapdoor'],
                ['Cold Cadaver', 'coldcadaver'],
                ['The Bitter Boogie', 'thebitterboogie'],
                ['N.G.R.I (Bloodstain)', 'ngribloodstain'],
                ['Time = Fate', 'timefate'],
                ['Time = $$$', 'timemoney'],
                ['Most Of What I Like', 'mostofwhatilike'],
                ['Paper Mâché', 'papermache'],
            ],
            'Nonagon Infinity' => [
                ['Robot Stop', 'robotstop'],
                ['Big Fig Wasp', 'bigfigwasp'],
                ['Gamma Knife', 'gammaknife'],
                ['People-Vultures', 'peoplevultures'],
                ['Mr. Beat', 'mrbeat'],
                ['Evil Death Roll', 'evildeathroll'],
                ['Invisible Face', 'invisibleface'],
                ['Wah Wah', 'wahwah'],
                ['Road Train', 'roadtrain'],
            ],
            'Flying Microtonal Banana' => [
                ['Rattlesnake', 'rattlesnake'],
                ['Melting', 'melting'],
                ['Open Water', 'openwater'],
                ['Sleep Drifter', 'sleepdrifter'],
                ['Billabong Valley', 'billabongvalley'],
                ['Anoxia', 'anoxia'],
                ['Doom City', 'doomcity'],
                ['Nuclear Fusion', 'nuclearfusion'],
                ['Flying Microtonal Banana', 'flyingmicrotonalbanana'],
            ],
            'Murder of the Universe' => [
                ['A New World', 'anewworld'],
                ['Altered Beast I', 'alteredbeasti'],
                ['Alter Me I', 'altermei'],
                ['Altered Beast II', 'alteredbeastii'],
                ['Alter Me II', 'altermeii'],
                ['Altered Beast III', 'alteredbeastiii'],
                ['Alter Me III', 'altermeiii'],
                ['Altered Beast IV', 'alteredbeastiv'],
                ['Life / Death', 'lifedeath'],
                ['Some Context', 'somecontext'],
                ['The Reticent Raconteur', 'thereticentraconteur'],
                ['The Lord of Lightning', 'thelordoflightning'],
                ['The Balrog', 'thebalrog'],
                ['The Floating Fire', 'thefloatingfire'],
                ['The Acrid Corpse', 'theacridcorpse'],
                ['Welcome to an Altered Future', 'welcometoanaleredfuture'],
                ['Digital Black', 'digitalblack'],
                ['Han-Tyumi, The Confused Cyborg', 'hantyumitheconfusedcyborg'],
                ['Soy-Protein Munt Machine', 'soyproteinmuntmachine'],
                ['Vomit Coffin', 'vomitcoffin'],
                ['Murder of the Universe', 'murderoftheuniverse'],
            ],
            'Sketches of Brunswick East' => [
                ['Sketches Of Brunswick East I', 'sketchesofbrunswickeasti'],
                ['Countdown', 'countdown'],
                ['D-Day', 'dday'],
                ['Tezeta', 'tezeta'],
                ['Cranes, Planes, Migraines', 'cranesplanesmigraines'],
                ['The Spider And Me', 'thespiderandme'],
                ['Sketches Of Brunswick East II', 'sketchesofbrunswickeastii'],
                ['Dusk To Dawn On Lygon St', 'dusktodawnonlygonst'],
                ['The Book', 'thebook'],
                ['A Journey To (S)Hell', 'ajourneyrtoshell'],
                ['Rolling Stoned', 'rollingstoned'],
                ['You Can Be Your Silhouette', 'youcanbeyoursilhouette'],
                ['Sketches Of Brunswick East III', 'sketchesofbrunswickeastiii'],
            ],
            'Polygondwanaland' => [
                ['Crumbling Castle', 'crumblingcastle'],
                ['Polygondwanaland', 'polygondwanaland'],
                ['The Castle In The Air', 'thecastleintheair'],
                ['Deserted Dunes Welcome Weary Feet', 'desertedduneswelcomeweraryfeet'],
                ['Inner Cell', 'innercell'],
                ['Loyalty', 'loyalty'],
                ['Horology', 'horology'],
                ['Tetrachromacy', 'tetrachromacy'],
                ['Searching...', 'searching'],
                ['The Fourth Colour', 'thefourthcolour'],
            ],
            'Gumboot Soup' => [
                ['Beginner\'s Luck', 'beginnersluck'],
                ['Greenhouse Heat Death', 'greenhouseheatdeath'],
                ['Barefoot Desert', 'barefootdesert'],
                ['Muddy Water', 'muddywater'],
                ['Superposition', 'superposition'],
                ['Down The Sink', 'downthesink'],
                ['The Great Chain of Being', 'thegreatचhainofbeing'],
                ['The Last Oasis', 'thelastoasis'],
                ['All Is Known', 'allisknown'],
                ['I\'m Sleepin\' In', 'imsleepin'],
                ['The Wheel', 'thewheel'],
            ],
            'Fishing for Fishies' => [
                ['Fishing For Fishies', 'fishingforfishies'],
                ['Boogieman Sam', 'boogιemansam'],
                ['The Bird Song', 'thebirdsong'],
                ['Plastic Boogie', 'plasticboogie'],
                ['The Cruel Millennial', 'thecruelmillennial'],
                ['Real\'s Not Real', 'realsnotreal'],
                ['This Thing', 'thisthing'],
                ['Acarine', 'acarine'],
                ['Cyboogie', 'cyboogie'],
            ],
            'Infest the Rats\' Nest' => [
                ['Planet B', 'planetb'],
                ['Mars For The Rich', 'marsfortherich'],
                ['Organ Farmer', 'organfarmer'],
                ['Superbug', 'superbug'],
                ['Venusian 1', 'venusian1'],
                ['Perihelion', 'perihelion'],
                ['Venusian 2', 'venusian2'],
                ['Self-Immolate', 'selfimmolate'],
                ['Hell', 'hell'],
            ],
            'K.G.' => [
                ['K.G.L.W', 'kglw'],
                ['Automation', 'automation'],
                ['Minimum Brain Size', 'minimumbrainsize'],
                ['Straws In The Wind', 'strawsinthewind'],
                ['Some of Us', 'someofus'],
                ['Ontology', 'ontology'],
                ['Intrasport', 'intrasport'],
                ['Oddlife', 'oddlife'],
                ['Honey', 'honey'],
                ['The Hungry Wolf Of Fate', 'thehungrywolfoffate'],
            ],
            'L.W.' => [
                ['If Not Now, Then When?', 'ifnotnowthenwhen'],
                ['O.N.E.', 'one'],
                ['Pleura', 'pleura'],
                ['Supreme Ascendancy', 'supremeascendancy'],
                ['Static Electricity', 'staticelectricity'],
                ['East West Link', 'eastwestlink'],
                ['Ataraxia', 'ataraxia'],
                ['See Me', 'seeme'],
                ['K.G.L.W', 'kglwlw'],
            ],
            'Butterfly 3000' => [
                ['Yours', 'yours'],
                ['Shanghai', 'shanghai'],
                ['Dreams', 'dreams'],
                ['Blue Morpho', 'bluemorpho'],
                ['Interior People', 'interiorpeople'],
                ['Catching Smoke', 'catchingsmoke'],
                ['2.02 Killer Year', '202killeryear'],
                ['Black Hot Soup', 'blackhotsoup'],
                ['Ya Love', 'yalove'],
                ['Butterfly 3000', 'butterfly3000'],
            ],
            'Made in Timeland' => [
                ['Timeland', 'timeland'],
                ['Smoke & Mirrors', 'smokemirrors'],
            ],
            'Omnium Gatherum' => [
                ['The Dripping Tap', 'thedrippingtap'],
                ['Magenta Mountain', 'magentamountain'],
                ['Kepler-22b', 'kepler22b'],
                ['Gaia', 'gaia'],
                ['Ambergris', 'ambergris'],
                ['Sadie Sorceress', 'sadiesorceress'],
                ['Evilest Man', 'evilestman'],
                ['The Garden Goblin', 'thegardengoblin'],
                ['Blame It On The Weather', 'blameitontheweather'],
                ['Persistence', 'persistence'],
                ['The Grim Reaper', 'thegrimreaper'],
                ['Presumptuous', 'presumptuous'],
                ['Predator X', 'predatorx'],
                ['Red Smoke', 'redsmoke'],
                ['Candles', 'candles'],
                ['The Funeral', 'thefuneral'],
            ],
            'Ice, Death, Planets, Lungs, Mushrooms and Lava' => [
                ['Mycelium', 'mycelium'],
                ['Ice V', 'icev'],
                ['Magma', 'magma'],
                ['Lava', 'lava'],
                ['Hell\'s Itch', 'hellsitch'],
                ['Iron Lung', 'ironlung'],
                ['Gliese 710', 'gliese710'],
            ],
            'Laminated Denim' => [
                ['The Land Before Timeland', 'thelandbeforetimeland'],
                ['Hypertension', 'hypertension'],
            ],
            'Changes' => [
                ['Change', 'change'],
                ['Hate Dancin\'', 'hatedancin'],
                ['Astroturf', 'astroturf'],
                ['No Body', 'nobody'],
                ['Gondii', 'gondii'],
                ['Exploding Suns', 'explodingsuns'],
                ['Short Change', 'shortchange'],
            ],
            'PetroDragonic Apocalypse' => [
                ['Motor Spirit', 'motorspirit'],
                ['Supercell', 'supercell'],
                ['Converge', 'converge'],
                ['Witchcraft', 'witchcraft'],
                ['Gila Monster', 'gilamonster'],
                ['Dragon', 'dragon'],
                ['Flamethrower', 'flamethrower'],
                ['Dawn of Eternal Night', 'dawnofeternalnight'],
            ],
            'The Silver Cord' => [
                ['Theia', 'theia'],
                ['The Silver Cord', 'thesilvercord'],
                ['Set', 'set'],
                ['Chang\'e', 'change'],
                ['Gilgamesh', 'gilgamesh'],
                ['Swan Song', 'swansong'],
                ['Extinction', 'extinction'],
            ],
            'Flight b741' => [
                ['Mirage City', 'miragecity'],
                ['Antarctica', 'antarctica'],
                ['Raw Feel', 'rawfeel'],
                ['Field of Vision', 'fieldofvision'],
                ['Hog Calling Contest', 'hogcallingcontest'],
                ['Le Risque', 'lerisque'],
                ['Flight b741', 'flightb741'],
                ['Sad Pilot', 'sadpilot'],
                ['Rats in the Sky', 'ratsinthesky'],
                ['Daily Blues', 'dailyblues'],
            ],
            'Phantom Island' => [
                ['Phantom Island', 'phantomisland'],
                ['Deadstick', 'deadstick'],
                ['Lonely Cosmos', 'lonelycosmos'],
                ['Eternal Return', 'eternalreturn'],
                ['Panpsych', 'panpsych'],
                ['Spacesick', 'spacesick'],
                ['Aerodynamic', 'aerodynamic'],
                ['Sea of Doubt', 'seaofdoubt'],
                ['Silent Spirit', 'silentspirit'],
                ['Grow Wings and Fly', 'growwingsandfly'],
            ],
        ],
        'moe.' => [
            'Fatboy' => [
                ['Y.O.Y.', 'yoy'],
                ['Long Island Girls Rule', 'longislandgirlsrule'],
                ['Dr. Graffenberg', 'drgraffenberg'],
                ['Don\'t Duck with Flo', 'dontduckwithflo'],
                ['Yodelittle', 'yodelittle'],
                ['Spine of a Dog', 'spineofadog'],
                ['Sensory Deprivation Bank', 'sensorydeprivationbank'],
                ['The Battle of Benny Hill', 'thebattleofbennyhill'],
            ],
            'Headseed' => [
                ['Akimbo', 'akimbo'],
                ['Mexico', 'mexico'],
                ['Timmy Tucker', 'timmytucker'],
                ['St. Augustine', 'staugustine'],
                ['Recreational Chemistry', 'recreationalchemistry'],
                ['Time Again', 'timeagain'],
                ['Yodelittle', 'yodelittleheadseed'],
                ['Brent Black', 'brentblack'],
                ['Threw It All Away', 'threwitallaway'],
                ['Time Ed', 'timeed'],
            ],
            'Tin Cans and Car Tires' => [
                ['Stranger Than Fiction', 'strangerthanfiction'],
                ['Spaz Medicine', 'spazmedicine'],
                ['Nebraska', 'nebraska'],
                ['Head', 'head'],
                ['Hi & Lo', 'hilo'],
                ['Plane Crash', 'planecrash'],
                ['Letter Home', 'letterhome'],
                ['Big World', 'bigworld'],
                ['Again And Again', 'againandagain'],
                ['It', 'it'],
                ['Happy Hour Hero', 'happyhourhero'],
                ['Queen Of The Rodeo', 'queenoftherodeo'],
            ],
            'Dither' => [
                ['Captain America', 'captainamerica'],
                ['Faker', 'faker'],
                ['Understand', 'understand'],
                ['Tgorm', 'tgorm'],
                ['So Long', 'solong'],
                ['New York City', 'newyorkcity'],
                ['Can\'t Seem to Find', 'cantseemtofind'],
                ['Water', 'water'],
                ['Tambourine', 'tambourine'],
                ['In a Big Country', 'inabigcountry'],
                ['Rise', 'rise'],
                ['Opium', 'opium'],
            ],
            'Season\'s Greetings from Moe' => [
                ['Carol of the Bells', 'carolofthebells'],
                ['Together at Christmas', 'togetherratchristmas'],
                ['Blue Christmas', 'bluechristmas'],
                ['We\'re a Couple of Misfits', 'wereacoupleoψfmisfits'],
                ['Oh Hanukah', 'ohhanukah'],
                ['Home', 'home'],
                ['Silent Night/Jesu, Joy of Man\'s Desiring', 'silentnightjesujoyofmansdesiring'],
                ['Linus and Lucy', 'linusandlucy'],
                ['Little Drummer Boy', 'littledrummerboy'],
                ['Jingle Bells', 'jinglebells'],
            ],
            'Wormwood' => [
                ['Not Coming Down', 'notcomingdown'],
                ['Wormwood', 'wormwood'],
                ['Okayalright', 'okayalright'],
                ['Rumble Strip', 'rumblestrip'],
                ['Gone', 'gone'],
                ['Organs', 'organs'],
                ['Crab Eyes', 'crabeyes'],
                ['Bullet', 'bullet'],
                ['Kyle\'s Song', 'kylessong'],
                ['Bend Sinister', 'bendsinister'],
                ['Kids', 'kids'],
                ['Kidstoys', 'kidstoys'],
                ['Shoot First', 'shootfirst'],
                ['Edison Laugh Record', 'edisonlaughrecord'],
            ],
            'The Conch' => [
                ['Blue Jeans Pizza', 'bluejeanspizza'],
                ['Lost Along The Way', 'lostalongtheway'],
                ['The Conch', 'theconch'],
                ['Tailspin', 'tailspin'],
                ['Tubing The River Styx', 'tubingtheriverstyx'],
                ['The Pit', 'thepit'],
                ['Another One Gone', 'anotheronegone'],
                ['Wind It Up', 'winditup'],
                ['Y Eaux Massa', 'yeauxmassa'],
                ['Down Boy', 'downboy'],
                ['She', 'she'],
                ['Where Does The Time Go', 'wheredoesthetimego'],
                ['Summer O I', 'summeroi'],
                ['The Road', 'theroad'],
                ['MacIntyre Range', 'macintyrerange'],
                ['The Col', 'thecol'],
                ['Brittle End', 'brittleend'],
            ],
            'Sticks and Stones' => [
                ['Cathedral', 'cathedral'],
                ['Sticks and Stones', 'sticksandstones'],
                ['Darkness', 'darkness'],
                ['Conviction Song', 'convictionsong'],
                ['Zed Nought Z', 'zednoughtz'],
                ['Deep This Time', 'deepthistime'],
                ['All Roads Lead to Home', 'allroadsleadtohome'],
                ['September', 'september'],
                ['Queen of Everything', 'queenofeverything'],
                ['Raise a Glass', 'raiseaglass'],
            ],
            'What Happened To The La Las' => [
                ['The Bones of Lazarus', 'thebonesoflazarus'],
                ['Haze', 'haze'],
                ['Downward Facing Dog', 'downwardfacingdog'],
                ['Rainshine', 'rainshine'],
                ['Smoke', 'smoke'],
                ['Paper Dragon', 'paperdragon'],
                ['Chromatic Nightmare', 'chromaticnightmare'],
                ['Puebla', 'puebla'],
                ['One Way Traffic', 'onewaytraffic'],
                ['Suck a Lemon', 'suckalemon'],
            ],
            'No Guts, No Glory' => [
                ['Annihilation Blues', 'annihilationblues'],
                ['White Lighting Tupentine', 'whitelightingtupentine'],
                ['This I Know', 'thisiknow'],
                ['Same Old Story', 'sameoldstory'],
                ['Silver Sun', 'silversun'],
                ['Calyphornya', 'calyphornya'],
                ['Little Miss Cup Half Empty', 'littlemisscuphalfempty'],
                ['Blond Hair And Blue Eyes', 'blondhairandblueeyes'],
                ['Do Or Die', 'doordie'],
                ['The Pines And The Apple Trees', 'thepinesandtheappletrees'],
                ['Billy Goat', 'billygoat'],
            ],
            'This Is Not, We Are' => [
                ['LL3', 'll3'],
                ['Crushing', 'crushing'],
                ['Jazz Cigarette', 'jazzcigarette'],
                ['Who You Calling Scared', 'whoyoucallingscared'],
                ['Dangerous Game', 'dangerουsgame'],
                ['Skitchin Buffalo', 'skitchinbuffalo'],
                ['Along For The Ride', 'alongfortheride'],
                ['Undertone', 'undertone'],
            ],
            'Circle of Giants' => [
                ['Yellow Tigers', 'yellowtigers'],
                ['Bat Country', 'batcountry'],
                ['Giants', 'giants'],
                ['Band In The Sky', 'bandinthesky'],
                ['In Stride', 'instride'],
                ['Tomorrow Is Another Day', 'tomorrowisanotherday'],
                ['Don\'tcha Know', 'dontchaknow'],
                ['Beautiful Mess', 'beautifulmess'],
                ['Ups And Downs', 'upsanddowns'],
                ['Living Again', 'livingagain'],
            ],
        ],
        'Guster' => [
            'Parachute' => [
                ['Fall in Two', 'fallintwo'],
                ['Mona Lisa', 'monalisa'],
                ['Love For Me', 'loveforme'],
                ['Window', 'window'],
                ['Eden', 'eden'],
                ['Scars & Stitches', 'scarsstitches'],
                ['The Prize', 'theprize'],
                ['Dissolve', 'dissolve'],
                ['Cocoon', 'cocoon'],
                ['Happy Frappy', 'happyfrappy'],
                ['Parachute', 'parachute'],
            ],
            'Goldfly' => [
                ['Great Escape', 'greatescape'],
                ['Demons', 'demons'],
                ['Perfect', 'perfect'],
                ['Airport Song', 'airportsong'],
                ['Medicine', 'medicine'],
                ['X-Ray Eyes', 'xrayeyes'],
                ['Grin', 'grin'],
                ['Getting Even', 'gettingeven'],
                ['Bury Me', 'buryme'],
                ['Rocketship', 'rocketship'],
            ],
            'Lost and Gone Forever' => [
                ['What You Wish For', 'whatyouwishfor'],
                ['Barrel of a Gun', 'barrelofagun'],
                ['Either Way', 'eitherway'],
                ['Fa Fa', 'fafa'],
                ['I Spy', 'ispy'],
                ['Center of Attention', 'centerofattention'],
                ['All The Way Up to Heaven', 'allthewayuptoheaven'],
                ['Happier', 'happier'],
                ['So Long', 'solong'],
                ['Two Points for Honesty', 'twopointsforhonesty'],
                ['Rainy Day', 'rainyday'],
            ],
            'Keep It Together' => [
                ['Diane', 'diane'],
                ['Careful', 'careful'],
                ['Amsterdam', 'amsterdam'],
                ['Backyard', 'backyard'],
                ['Homecoming King', 'homecomingking'],
                ['Ramona', 'ramona'],
                ['Jesus On The Radio', 'jesusontheradio'],
                ['Keep It Together', 'keepittogether'],
                ['Come Downstairs & Say Hello', 'comedownstairssayhello'],
                ['Red Oyster Cult', 'redoystercult'],
                ['Long Way Down', 'longwaydown'],
                ['I Hope Tomorrow Is Like Today', 'ihopetomorrowisliketoday'],
                ['Silence', 'silence'],
                ['Two at a Time', 'twoatatime'],
            ],
            'Ganging Up on the Sun' => [
                ['Lightning Rod', 'lightningrod'],
                ['Satellite', 'satellite'],
                ['Manifest Destiny', 'manifestdestiny'],
                ['One Man Wrecking Machine', 'onemanwreckingmachine'],
                ['The Captain', 'thecaptain'],
                ['The New Underground', 'thenewunderground'],
                ['Ruby Falls', 'rubyfalls'],
                ['C\'Mon', 'cmon'],
                ['Empire State', 'empirestate'],
                ['Dear Valentine', 'dearvalentine'],
                ['The Beginning of the End', 'thebeginningoftheend'],
                ['Hang On', 'hangon'],
            ],
            'Easy Wonderful' => [
                ['Architects & Engineers', 'architectsengineers'],
                ['Do You Love Me', 'doyouloveme'],
                ['On the Ocean', 'ontheocean'],
                ['This Could All Be Yours', 'thiscouldallbeyours'],
                ['Stay With Me Jesus', 'staywithmejesus'],
                ['Bad Bad World', 'badbadworld'],
                ['This Is How It Feels to Have a Broken Heart', 'thisishowifeelstohaveabrokenheart'],
                ['What You Call Love', 'whatyoucalllove'],
                ['That\'s No Way to Get to Heaven', 'thatsnowaytogοettoheaven'],
                ['Jesus and Mary', 'jesusandmary'],
                ['Hercules', 'hercules'],
                ['Do What You Want', 'dowhatyouwant'],
            ],
            'Evermotion' => [
                ['Long Night', 'longnight'],
                ['Endlessly', 'endlessly'],
                ['Doin\' It By Myself', 'doinitbymyself'],
                ['Lazy Love', 'lazylove'],
                ['Simple Machine', 'simplemachine'],
                ['Expectation', 'expectation'],
                ['Gangway', 'gangway'],
                ['Kid Dreams', 'kiddreams'],
                ['Never Coming Down', 'nevercomingdown'],
                ['It Is Just What It Is', 'itisjustwhatitis'],
                ['Farewell', 'farewell'],
            ],
            'Look Alive' => [
                ['Look Alive', 'lookalive'],
                ['Don\'t Go', 'dontgo'],
                ['Hard Times', 'hardtimes'],
                ['Hello Mister Sun', 'hellomistersun'],
                ['Overexcited', 'overexcited'],
                ['Summertime', 'summertime'],
                ['Terrified', 'terrified'],
                ['Mind Kontrol', 'mindkontrol'],
                ['Not for Nothing', 'notfornothing'],
            ],
            'Ooh La La' => [
                ['This Heart Is Occupied', 'thisheartisoccupied'],
                ['When We Were Stars', 'whenwewerestars'],
                ['All Day', 'allday'],
                ['My Kind', 'mykind'],
                ['Keep Going', 'keepgoing'],
                ['Gauguin, Cézanne (Everlasting Love)', 'gauguincezanneeverlastinglove'],
                ['Witness Tree', 'witnesstree'],
                ['Black Balloon', 'blackballoon'],
                ['The Elevator', 'theelevator'],
                ['Maybe We\'re Alright', 'maybewerealright'],
            ],
        ],
        'Ween' => [
            'The Pod' => [
                ['Strap On That Jammy Pac', 'straponthatjammypac'],
                ['Dr. Rock', 'drrock'],
                ['Frank', 'frank'],
                ['Sorry Charlie', 'sorrycharlie'],
                ['The Stallion (Pt. 1)', 'thestallionpt1'],
                ['Pollo Asado', 'polloasado'],
                ['Right To The Ways And The Rules Of The World', 'righttothewaysandtherulesoftheworld'],
                ['Captain Fantasy', 'captainfantasy'],
                ['Demon Sweat', 'demonsweat'],
                ['Molly', 'molly'],
                ['Can U Taste The Waste?', 'canutastethewaste'],
                ['Don\'t Sweat It', 'dontsweatit'],
                ['Awesome Sound', 'awesomesound'],
                ['Laura', 'laura'],
                ['Boing', 'boing'],
                ['Mononucleosis', 'mononucleosis'],
                ['Oh My Dear (Falling In Love)', 'ohmydearfallinginlove'],
                ['Sketches Of Winkle', 'sketchesofwinkle'],
                ['Alone', 'alone'],
                ['Moving Away', 'movingaway'],
                ['She Fucks Me', 'shefucksme'],
                ['Pork Roll Egg And Cheese', 'porkrolleggandcheese'],
                ['The Stallion (Pt. 2)', 'thestallionpt2'],
            ],
            'Pure Guava' => [
                ['Little Birdy', 'littlebirdy'],
                ['Tender Situation', 'tendersituation'],
                ['The Stallion Pt. 3', 'thestallionpt3'],
                ['Big Jilm', 'bigjilm'],
                ['Push Th\' Little Daisies', 'pushthlittledaisies'],
                ['The Goin\' Gets Tough From The Getgo', 'thegoingetstoughfromthegetgo'],
                ['Reggaejunkiejew', 'reggaejunkiejew'],
                ['I Play It Off Legit', 'iplayitofflegit'],
                ['Pumpin\' 4 The Man', 'pumpin4theman'],
                ['Sarah', 'sarah'],
                ['Springtheme', 'springtheme'],
                ['Flies On My Dick', 'fliesonmydick'],
                ['I Saw Gener Cryin\' In His Sleep', 'isawgenercryininhissleep'],
                ['Touch My Tooter', 'touchmytooter'],
                ['Mourning Glory', 'mourningglory'],
                ['Loving U Thru It All', 'lovinguthruiall'],
                ['Hey Fat Boy (Asshole)', 'heyfatboyasshole'],
                ['Don\'t Get 2 Close (2 My Fantasy)', 'dontget2close2myfantasy'],
                ['Poop Ship Destroyer', 'poopshipdestroyer'],
            ],
            'Chocolate and Cheese' => [
                ['Take Me Away', 'takemeaway'],
                ['Spinal Meningitis (Got Me Down)', 'spinalmeningitisgotmedown'],
                ['Freedom of \'76', 'freedomof76'],
                ['I Can\'t Put My Finger on It', 'icantputmyfingeronit'],
                ['A Tear for Eddie', 'atearforeddie'],
                ['Roses Are Free', 'rosesarefree'],
                ['Baby Bitch', 'babybitch'],
                ['Mister, Would You Please Help My Pony?', 'misterwouldyoupleasehelpmypony'],
                ['Drifter in the Dark', 'drifterinthedark'],
                ['Voodoo Lady', 'voodoolady'],
                ['Joppa Road', 'jopparoad'],
                ['Candi', 'candi'],
                ['Buenas Tardes Amigo', 'buenastardesamigo'],
                ['The HIV Song', 'thehivsong'],
                ['What Deaner Was Talkin\' About', 'whatdeanerwastalkinabout'],
                ['Don\'t Shit Where You Eat', 'dontshitwhereyoueat'],
            ],
            '12 Golden Country Greats' => [
                ['I\'m Holding You', 'imholdingyou'],
                ['Japanese Cowboy', 'japanesecowboy'],
                ['Piss up a Rope', 'pissuparope'],
                ['I Don\'t Wanna Leave You on the Farm', 'idontwannaleaveyouonthefarm'],
                ['Pretty Girl', 'prettygirl'],
                ['Powder Blue', 'powderblue'],
                ['Mister Richard Smoker', 'misterrichardsmoker'],
                ['Help Me Scrape the Mucus off My Brain', 'helpmescrapethemucusoffmybrain'],
                ['You Were the Fool', 'youwerethefool'],
                ['Fluffy', 'fluffy'],
            ],
            'The Mollusk' => [
                ['I\'m Dancing in the Show Tonight', 'imdancingintheshowtonight'],
                ['The Mollusk', 'themollusk'],
                ['Polka Dot Tail', 'polkadottail'],
                ['I\'ll Be Your Jonny on the Spot', 'illbeyourjonnyonthespot'],
                ['Mutilated Lips', 'mutilatedlips'],
                ['The Blarney Stone', 'theblarneystone'],
                ['It\'s Gonna Be (Alright)', 'itsgonnabealright'],
                ['The Golden Eel', 'thegoldeneel'],
                ['Cold Blows the Wind', 'coldblowsthewind'],
                ['Pink Eye (On My Leg)', 'pinkeyeonmyleg'],
                ['Waving My Dick in the Wind', 'wavingmydickinthewind'],
                ['Buckingham Green', 'buckinghamgreen'],
                ['Ocean Man', 'oceanman'],
                ['She Wanted to Leave', 'shewantedtoleave'],
            ],
            'White Pepper' => [
                ['Exactly Where I\'m At', 'exactlywhereimat'],
                ['The Grobe', 'thegrobe'],
                ['Flutes of Chi', 'flutesofchi'],
                ['Even If You Don\'t', 'evenifyoudont'],
                ['Bananas and Blow', 'bananasandblow'],
                ['Stroker Ace', 'strokerace'],
                ['Ice Castles', 'icecastles'],
                ['Back to Basom', 'backtobasom'],
                ['Pandy Fackler', 'pandyfackler'],
                ['Stay Forever', 'stayforever'],
                ['Falling Out', 'fallingout'],
                ['She\'s Your Baby', 'shesyourbaby'],
            ],
            'Quebec' => [
                ['It\'s Gonna Be a Long Night', 'itsgonnaבealongnight'],
                ['Zoloft', 'zoloft'],
                ['Transdermal Celebration', 'transdermalcelebration'],
                ['Among His Tribe', 'amonghistribe'],
                ['So Many People in the Neighborhood', 'somanypeople­intheneighborhood'],
                ['Tried and True', 'triedandtrue'],
                ['Happy Colored Marbles', 'happycoloredmarbles'],
                ['Hey There Fancypants', 'heytherefancypants'],
                ['Captain', 'captain'],
                ['Chocolate Town', 'chocolatetown'],
                ['I Don\'t Want It', 'idontwantit'],
                ['The Fucked Jam', 'thefuckedjam'],
                ['Alcan Road', 'alcanroad'],
                ['The Argus', 'theargus'],
                ['If You Could Save Yourself (You\'d Save Us All)', 'ifyoucoulsaveyourselfyoudsaveusall'],
            ],
            'La Cucaracha' => [
                ['Fiesta', 'fiesta'],
                ['Blue Balloon', 'blueballoon'],
                ['Friends', 'friends'],
                ['Object', 'object'],
                ['Learnin\' To Love', 'learnintolove'],
                ['With My Own Bare Hands', 'withmyownbarehands'],
                ['The Fruit Man', 'thefruitman'],
                ['Spirit Walker', 'spiritwalker'],
                ['Shamemaker', 'shamemaker'],
                ['Sweetheart', 'sweetheart'],
                ['Lullaby', 'lullaby'],
                ['Woman And Man', 'womanandman'],
                ['Your Party', 'yourparty'],
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
                'AddTracksGroup3 completed. Added %d tracks for %d artists.',
                $totalTracks,
                count(self::TRACKS)
            ));
        } catch (\Exception $e) {
            $this->logger->error('Failed to add tracks (Group 3): ' . $e->getMessage());
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
