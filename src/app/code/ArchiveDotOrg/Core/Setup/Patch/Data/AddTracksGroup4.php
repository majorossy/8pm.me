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
 * Add Tracks Group 4 Data Patch
 *
 * Adds tracks for Keller Williams, My Morning Jacket, Lettuce, and Umphrey's McGee albums.
 */
class AddTracksGroup4 implements DataPatchInterface
{
    /**
     * Track data structure
     * Format: Artist Name => Album Name => [[track_name, track_url_key], ...]
     */
    private const TRACKS = [
        'Keller Williams' => [
            'Freek' => [
                ['Get On Up/Sanford & Son', 'getonupsanfordson'],
                ['The Juggler', 'thejuggler'],
                ['Turn In Difference', 'turnindifference'],
                ['Friendly Pyramid', 'friendlypyramid'],
                ['In The Middle', 'inthemiddle'],
                ['Chillin\'', 'chillin'],
                ['The Miss Annie Overture In A', 'themissannieovertureina'],
                ['Passapatanzy', 'passapatanzy'],
                ['Shapes Of Change', 'shapesofchange'],
                ['The River', 'theriver'],
                ['A Day That Never Was', 'adaythatneverwas'],
            ],
            'Buzz' => [
                ['Sunny Rain', 'sunnyrain'],
                ['Sally Sullivan', 'sallysullivan'],
                ['Relaxation Station', 'relaxationstation'],
                ['Fuel for the Road', 'fuelfortheroad'],
                ['Stinky Green', 'stinkygreen'],
                ['Yoni', 'yoni'],
                ['Over Dub', 'overdub'],
                ['Anyhow Anyway', 'anyhowanyway'],
                ['Inhale to the Chief', 'inhaletothechief'],
                ['Killer Waves', 'killerwaves'],
                ['Best Feeling', 'bestfeeling'],
                ['Same Ol\'', 'sameol'],
                ['Molly Maloy', 'mollymaloy'],
            ],
            'Spun' => [
                ['Running On Fumes', 'runningonfumes'],
                ['Tribe', 'tribe'],
                ['Blazeabago', 'blazeabago'],
                ['Thirsty In The Rain', 'thirstyintherain'],
                ['221in', 'track221in'],
                ['Stargate', 'stargate'],
                ['Spun', 'spun'],
                ['In A Big Country', 'inabigcountry'],
                ['Portapotty', 'portapotty'],
                ['Theme From The Pink Panther', 'themefromthepinkpanther'],
                ['Sleeping Giant', 'sleepinggiant'],
            ],
            'Breathe' => [
                ['Stupid Questions', 'stupidquestions'],
                ['Brunette', 'brunette'],
                ['Breathe', 'breathe'],
                ['Best Feeling', 'bestfeelingbreathе'],
                ['Bounty Hunter', 'bountyhunter'],
                ['Vacate', 'vacate'],
                ['Roshambo', 'roshambo'],
                ['Revelation', 'revelation'],
                ['Lightning', 'lightning'],
                ['Blatant Ripoff', 'blatantripoff'],
                ['Not Of This Earth', 'notofthisearth'],
                ['Rockumal', 'rockumal'],
                ['Callaloo And The Red Snapper', 'callaloοandtheredsnapper'],
            ],
            'Loop' => [
                ['Thin Mint', 'thinmint'],
                ['Kiwi And The Apricot', 'kiwiandtheapricot'],
                ['More Than A Little', 'morethanalittle'],
                ['Vacate', 'vacateloop'],
                ['Blatant Ripoff', 'blatantripoffloop'],
                ['Kidney In A Cooler', 'kidneyinacooler'],
                ['Landlord', 'landlord'],
                ['Turn In Difference', 'turnindifferenceloop'],
                ['No Hablo Espanol', 'nohabloespanol'],
                ['Rockumal', 'rockumalloop'],
                ['Stupid Questions', 'stupidquestionsloop'],
                ['Inhale To The Chief', 'inhaletοthechiefloop'],
                ['Nomini', 'nomini'],
            ],
            'Laugh' => [
                ['Freeker By The Speaker', 'freekerbythespeaker'],
                ['One Hit Wonder', 'onehitwonder'],
                ['Hunting Charlie', 'huntingcharlie'],
                ['Alligator Alley', 'alligatoralley'],
                ['Spring Buds', 'springbuds'],
                ['Mental Instra', 'mentalinstra'],
                ['Vabeeotchay', 'vabeeotchay'],
                ['Bob Rules', 'bobrules'],
                ['Freakshow', 'freakshow'],
                ['Gallivanting', 'gallivanting'],
                ['God Is My Palm Pilot', 'godismypalmpilot'],
                ['Crooked', 'crooked'],
                ['Old Lady From Carlsbad', 'oldladyfromcarlsbad'],
                ['Kidney In A Cooler', 'kidneyinacoolerlaugh'],
                ['Freeker Reprise', 'freekerreprise'],
            ],
            'Dance' => [
                ['Tweeker', 'tweeker'],
                ['Bazooka Speaker Funk', 'bazookaspeakerfunk'],
                ['Chunter', 'chunter'],
                ['Yeah', 'yeah'],
                ['Room to Grow', 'roomtogrow'],
                ['Mental Floss', 'mentalfloss'],
                ['Better Than Reality', 'betterthanreality'],
                ['Barker', 'barker'],
                ['Flabbergasting', 'flabbergasting'],
                ['Chickahominy Fred', 'chickahominyfred'],
                ['Worth All the Dough', 'worthallthedough'],
                ['Butt Sweat', 'buttsweat'],
            ],
            'Home' => [
                ['Love Handles', 'lovehandles'],
                ['Apparition', 'apparition'],
                ['Tubeular', 'tubeular'],
                ['Victory Song', 'victorysong'],
                ['Butt Ass Nipple', 'buttassnipple'],
                ['Dogs', 'dogs'],
                ['Skitso', 'skitso'],
                ['Moving Sidewalk', 'movingsidewalk'],
                ['Sheebs', 'sheebs'],
                ['Above the Thunder', 'abovethethunder'],
                ['Art', 'art'],
                ['Casa Quetzal', 'casaquetzal'],
                ['Bitch Monkey', 'bitchmonkey'],
                ['You Are What You Eat', 'youarewhatyoueat'],
                ['Zilla', 'zilla'],
                ['Sorry From the Shower', 'sorryfromtheshower'],
            ],
            'Stage' => [
                ['Tubeular', 'tubeularstage'],
                ['Rapper\'s Delight', 'rappersdelight'],
                ['Skitso', 'skitsostage'],
                ['Under Pressure', 'underpressure'],
                ['Shinjuku', 'shinjuku'],
                ['Keep It Simple', 'keepitsimple'],
                ['Dance of the Freek', 'danceofthefreek'],
                ['Blazeabago', 'blazeabagostage'],
                ['Let\'s Go Dancing', 'letsgodancing'],
                ['Blazeabago', 'blazeabagostage2'],
                ['Moondance', 'moondance'],
                ['Stargate', 'stargatestage'],
                ['Hum Diddly Eye', 'humdiddlyeye'],
                ['One Way Johnny', 'onewayjohnny'],
                ['Novelty Song', 'noveltysong'],
                ['Shapes of M+M\'s', 'shapesofmandms'],
                ['Don\'t Stop \'Til You Get Enough', 'dontstoptιlyougetenough'],
                ['Dudelywah', 'dudelywah'],
                ['Bird Song', 'birdsong'],
                ['For What It\'s Worth', 'forwhatitsworth'],
                ['Prelude to a Cracker', 'preludetoacracker'],
                ['Cracker Ass Cracker', 'crackerasscracker'],
                ['Zilla a trois', 'zillatrois'],
                ['Gate Crashers Suck', 'gatecrasherssuck'],
                ['Balcony Baby', 'balconybaby'],
                ['Celebrate Your Youth', 'celebrateyouryouth'],
                ['My Sisters and Brothers Boob Job', 'mysistersandbrothersbοοbjob'],
            ],
            'Grass' => [
                ['Goof Balls', 'goofballs'],
                ['Another Brick In The Wall', 'anotherbrickinthewall'],
                ['Mary Jane\'s Last Breakdown', 'maryjaneslastbreakdown'],
                ['Stunt Double', 'stuntdouble'],
                ['New Horizons', 'newhorizons'],
                ['Loser', 'loser'],
                ['Crater In The Back Yard', 'craterinthebackyard'],
                ['Dupree\'s Diamond Blues', 'dupreesdiamondblues'],
                ['Local', 'local'],
                ['I\'m Just Here To Get My Baby Out Of Jail', 'imjustheretοgetmybabyoutofjail'],
            ],
            'Dream' => [
                ['Play This', 'playthis'],
                ['Celebrate Your Youth', 'celebrateyouryouthdream'],
                ['Cadillac', 'cadillac'],
                ['Ninja of Love', 'ninjaoflove'],
                ['Kiwi and the Apricot', 'kiwiandtheapricotdream'],
                ['People Watchin\'', 'peoplewatchin'],
                ['Cookies', 'cookies'],
                ['Rainy Day', 'rainyday'],
                ['Sing For My Dinner', 'singformydinner'],
                ['Restraint', 'restraint'],
                ['Life', 'life'],
                ['Twinkle', 'twinkle'],
                ['Got No Feathers', 'gotnofeathers'],
                ['Slo Mo Balloon', 'slοmoballoon'],
                ['Lil\' Sexy Blues', 'lilsexyblues'],
                ['Bendix/Dance Hippie', 'bendixdancehippie'],
            ],
            '12' => [
                ['Turn In Difference', 'turnindifference12'],
                ['Anyhow Anyway', 'anyhοwanyway12'],
                ['Tribe', 'tribe12'],
                ['Breathe', 'breathe12'],
                ['More Than a Little', 'morethanalittle12'],
                ['Freeker By the Speaker', 'freekerbythespeaker12'],
                ['Butt Sweat', 'buttsweat12'],
                ['Apparition', 'apparition12'],
                ['Keep It Simple', 'keepitsimple12'],
                ['Local', 'local12'],
                ['People Watchin\'', 'peoplewatchin12'],
                ['Freshies', 'freshies'],
            ],
            'Odd' => [
                ['Environmental Song', 'environmentalsong'],
                ['Day At The Office', 'dayattheoffice'],
                ['Spartan Darn It', 'spartandarnit'],
                ['Groove Of The Storm', 'grooveofthestorm'],
                ['Elephorse', 'elephorse'],
                ['Lost', 'lost'],
                ['Warning', 'warning'],
                ['Tundra', 'tundra'],
                ['Ultimate', 'ultimate'],
                ['Doobie In My Pocket', 'doobieinmypocket'],
                ['Ear Infection', 'earinfection'],
                ['Song For Fela', 'songforfela'],
            ],
            'Thief' => [
                ['Don\'t Cuss the Fiddle', 'dontcussthefiddle'],
                ['Uncle Disney', 'uncledisney'],
                ['Rehab', 'rehab'],
                ['Get it While You Can', 'getitwhileyoucan'],
                ['Cold Roses', 'coldroses'],
                ['Mountains of the Moon', 'mountainsofthemoon'],
                ['Teen Angst', 'teenangst'],
                ['Wind\'s on Fire', 'windsonfire'],
                ['Switch and the Spur', 'switchandthespur'],
                ['Sex and Candy', 'sexandcandy'],
                ['Pepper', 'pepper'],
                ['Bath of Fire', 'bathoffire'],
                ['The Year 2003 Minus 25', 'theyear2003minus25'],
            ],
            'Kids' => [
                ['My Neighbor Is Happy Again', 'myneighborishappyagain'],
                ['Car Seat', 'carseat'],
                ['Because I Said So', 'becauseisaidso'],
                ['Hula Hoop to Da Loop', 'hulahooptodaloop'],
                ['Horse Backrider', 'horsebackrider'],
                ['Taking a Bath', 'takingabath'],
                ['Good Advice', 'goodadvice'],
                ['Keep It on the Paper', 'keepitonthepaper'],
                ['Hey Little Baby', 'heylittlebaby'],
                ['Mama Tooted', 'mamatooted'],
                ['Soakie Von Soakerman', 'soakievonsoakerman'],
                ['Lucy Lawcy', 'lucylawcy'],
                ['Grandma\'s Feather Bed', 'grandmasfeatherbed'],
                ['The the Fastest Song in the World', 'thethefastestsongintheworld'],
            ],
            'Bass' => [
                ['The Sun & Moon\'s Vagenda', 'thesunmoonsvagenda'],
                ['2bu', '2bu'],
                ['Hey Ho Jorge', 'heyhojorge'],
                ['I Am Elvis', 'iamelvis'],
                ['Hollywood Freaks', 'hollywoodfreaks'],
                ['Thinking Out Loud', 'thinkingoutloud'],
                ['High', 'high'],
                ['Buena', 'buena'],
                ['Super Hot', 'superhot'],
                ['Hobo Jungle', 'hobojungle'],
                ['Positive', 'positive'],
            ],
            'Pick' => [
                ['Something Else', 'somethingelse'],
                ['American Car', 'americancar'],
                ['Messed Up Just Right', 'messedupjustright'],
                ['Mullet Cut', 'mulletcut'],
                ['Graveyard Shift', 'graveyardshift'],
                ['I Am Elvis', 'iamelvispick'],
                ['What A Waste', 'whatawaste'],
                ['Broken Convertible', 'brokenconvertible'],
                ['I\'m Amazed', 'imamazed'],
                ['Price Tag', 'pricetag'],
                ['Sexual Harassment', 'sexualharassment'],
                ['Bumper Sticker', 'bumpersticker'],
            ],
            'Dos' => [
                ['Bertha', 'bertha'],
                ['Jack Straw', 'jackstraw'],
                ['Sugaree', 'sugaree'],
                ['Greatest Story Ever Told', 'greateststοryevertold'],
                ['Till the Morning Comes', 'tillthemorningcomes'],
                ['High Time', 'hightime'],
                ['Samson and Delilah', 'samsonanddelilah'],
                ['Althea', 'althea'],
                ['Tennessee Jed', 'tennesseejed'],
                ['Shakedown Street', 'shakedownstreet'],
            ],
            'Raw' => [
                ['Cookie\'s', 'cookies'],
                ['Short Ballad of Camp Zoe', 'shortballadofcampzoe'],
                ['2BU', '2buraw'],
                ['Right Here', 'righthere'],
                ['Return to the Moon', 'returntothemoon'],
                ['Ella', 'ella'],
                ['Thanks Leo', 'thanksleo'],
                ['I Forgot', 'iforgot'],
                ['Short Show', 'shortshow'],
                ['Ticks When Told', 'tickswhentold'],
            ],
            'Sync' => [
                ['Ripped 6-Pack', 'ripped6pack'],
                ['Cheaper by the Bale', 'cheaperbythebale'],
                ['Watchoowantgurl', 'watchoowantgurl'],
                ['Baby Mama', 'babymama'],
                ['Hategreedlove', 'hategreedlove'],
                ['Missing Remote', 'missingremote'],
                ['In the Middle', 'inthemiddlesync'],
                ['Running on Fumes', 'runningonfumessync'],
            ],
            'Speed' => [
                ['Little Too Late', 'littletoolate'],
                ['Hash Pipe', 'hashpipe'],
                ['Medulla Oblongatta', 'medullaoblongatta'],
                ['Livin\' la Vida Loca', 'livinlavidaloca'],
                ['Criminal', 'criminal'],
                ['Slow Burn', 'slowburn'],
                ['Peaches', 'peaches'],
                ['Road House Blues', 'roadhouseblues'],
                ['Spirits', 'spirits'],
                ['Lizard Lady', 'lizardlady'],
                ['Island in the Sun', 'islandinthesun'],
                ['Do It on the Strings', 'doitonthestrings'],
            ],
        ],
        'My Morning Jacket' => [
            'The Tennessee Fire' => [
                ['Heartbreakin Man', 'heartbreakinman'],
                ['They Ran', 'theyran'],
                ['The Bear', 'thebear'],
                ['Nashville To Kentucky', 'nashvilletokentucky'],
                ['Old Sept Blues', 'oldseptblues'],
                ['If All Else Fails', 'ifallelsefails'],
                ['It\'s About Twilight Now', 'itsabouttwilightnow'],
                ['Evelyn Is Not Real', 'evelynisnotreal'],
                ['War Begun', 'warbegun'],
                ['Picture Of You', 'pictureofyou'],
                ['I Will Be There When You Die', 'iwillbetherewhenyoudie'],
                ['The Dark', 'thedark'],
                ['By My Car', 'bymycar'],
                ['Butch Cassidy', 'butchcassidy'],
                ['I Think I\'m Going To Hell', 'ithinkimgoingtohell'],
                ['Instrumental', 'instrumental'],
            ],
            'At Dawn' => [
                ['At Dawn', 'atdawn'],
                ['Lowdown', 'lowdown'],
                ['The Way That He Sings', 'thewaythathesingѕ'],
                ['Death Is The Easy Way', 'deathistheeasyway'],
                ['Hopefully', 'hopefully'],
                ['Bermuda Highway', 'bermudahighway'],
                ['Honest Man', 'honestman'],
                ['Xmas Curtain', 'xmascurtain'],
                ['Just Because I Do', 'justbecauseido'],
                ['If It Smashes Down', 'ifitsmashesdown'],
                ['I Needed It Most', 'ineededitmost'],
                ['Phone Went West', 'phonewentwest'],
                ['Strangulation', 'strangulation'],
                ['Untitled Bonus Track', 'untitledbonustrack'],
            ],
            'Evil Urges' => [
                ['Evil Urges', 'evilurges'],
                ['Touch Me I\'m Going To Scream Pt.1', 'touchmeimgoingtoscreampt1'],
                ['Highly Suspicious', 'highlysuspicious'],
                ['I\'m Amazed', 'imamazedmmj'],
                ['Thank You Too!', 'thankyoutoo'],
                ['Sec Walkin\'', 'secwalkin'],
                ['Two Halves', 'twohalves'],
                ['Librarian', 'librarian'],
                ['Look At You', 'lookatyou'],
                ['Aluminum Park', 'aluminumpark'],
                ['Remnants', 'remnants'],
                ['Smokin\' From Shootin\'', 'smokinfromshootin'],
                ['Touch Me I\'m Going To Scream Pt. 2', 'touchmeimgoingtoscreampt2'],
                ['Good Intentions', 'goodintentions'],
            ],
            'Circuital' => [
                ['Victory Dance', 'victorydance'],
                ['Circuital', 'circuital'],
                ['The Day Is Coming', 'thedayiscoming'],
                ['Wonderful (The Way I Feel)', 'wonderfulthewayifeel'],
                ['Outta My System', 'outtamysystem'],
                ['Holdin\' On To Black Metal', 'holdіnontoЬlackmetal'],
                ['First Light', 'firstlight'],
                ['You Wanna Freak Out', 'youwannafreakout'],
                ['Slow Slow Tune', 'slowslowtune'],
                ['Movin\' Away', 'movinaway'],
            ],
            'The Waterfall' => [
                ['Believe (Nobody Knows)', 'believenobodyknows'],
                ['Compound Fracture', 'compoundfracture'],
                ['Like A River', 'likeariver'],
                ['In Its Infancy (The Waterfall)', 'initsinfancythewaterfall'],
                ['Get The Point', 'getthepoint'],
                ['Spring (Among The Living)', 'springamongtheliving'],
                ['Thin Line', 'thinline'],
                ['Big Decisions', 'bigdecisions'],
                ['Tropics (Erase Traces)', 'tropicserasetraces'],
                ['Only Memories Remain', 'οnlymemoriesremain'],
            ],
            'The Waterfall II' => [
                ['Spinning My Wheels', 'spinningmywheels'],
                ['Still Thinkin', 'stillthinkin'],
                ['Climbing The Ladder', 'climbingtheladder'],
                ['Feel You', 'feelyou'],
                ['Beautiful Love (Wasn\'t Enough)', 'beautifullοvewasntеnough'],
                ['Magic Bullet', 'magicbullet'],
                ['Run It', 'runit'],
                ['Wasted', 'wasted'],
                ['Welcome Home', 'welcomehome'],
                ['The First Time', 'thefirsttime'],
            ],
            'My Morning Jacket' => [
                ['Regularly Scheduled Programming', 'regularlyscheduledprogramming'],
                ['Love Love Love', 'lοvelοvelove'],
                ['In Color', 'incolor'],
                ['Least Expected', 'leastexpected'],
                ['Never In The Real World', 'neverintherealworld'],
                ['The Devil\'s In The Details', 'thedevilsinthedetails'],
                ['Lucky To Be Alive', 'luckytobealive'],
                ['Complex', 'complex'],
                ['Out of Range, Pt. 2', 'outofrangept2'],
                ['Penny For Your Thoughts', 'pennyfοryourthoughts'],
                ['I Never Could Get Enough', 'inevercouldgetenough'],
            ],
            'Is' => [
                ['Out in The Open', 'outintheopen'],
                ['Half a Lifetime', 'halfalifetime'],
                ['Everyday Magic', 'everydaymagic'],
                ['I Can Hear Your Love', 'іcanhearyourlove'],
                ['Time Waited', 'timewaited'],
                ['Beginning From The Ending', 'beginningfromtheending'],
                ['Lemme Know', 'lemmeknow'],
                ['Squid Ink', 'squidink'],
                ['Die For It', 'dieforit'],
                ['River Road', 'riverroad'],
            ],
        ],
        'Lettuce' => [
            'Outta Here' => [
                ['Outta Here', 'outtahere'],
                ['The Dump', 'thedump'],
                ['Squadlive', 'squadlive'],
                ['Back in Effect', 'backineffect'],
                ['Twisted', 'twisted'],
                ['Superfred', 'superfred'],
                ['Reunion', 'reunion'],
                ['The Flu', 'theflu'],
                ['Nyack', 'nyack'],
                ['Hang up Your Hang Ups', 'hangupyourhangups'],
                ['Nyack - Live', 'nyacklive'],
            ],
            'Rage!' => [
                ['Blast Off', 'blastoff'],
                ['Sam Huff\'s Flying Ragin\' Machine', 'samhuffsflyingraginmachine'],
                ['Move on Up', 'moveonup'],
                ['King of the Burgs', 'kingoftheburgs'],
                ['Need to Understand', 'needtounderstand'],
                ['Last Suppit', 'lastsuppit'],
                ['Dizzer', 'dizzer'],
                ['Makin\' My Way Back Home', 'makinmywaybackhome'],
                ['Salute', 'salute'],
                ['Speak E.Z.', 'speakez'],
                ['Express Yourself', 'expressyourself'],
                ['Relax', 'relax'],
                ['By Any Shmeeans Necessary', 'byanyshmeeansnecessary'],
                ['Mr. Yancey', 'mryancey'],
            ],
            'Crush' => [
                ['The Force', 'theforce'],
                ['Get Greasy', 'getgreasy'],
                ['Chief', 'chief'],
                ['\'Lude 1', 'lude1'],
                ['Phyllis', 'phyllis'],
                ['Sounds Like A Party', 'soundslikeaparty'],
                ['The Lobbyist', 'thelobbyist'],
                ['\'Lude 2', 'lude2'],
                ['Trillogy', 'trillogy'],
                ['Pocket Change', 'pocketchange'],
                ['The New Reel', 'thenewreel'],
                ['\'Lude 3', 'lude3'],
                ['He Made A Woman Out Of Me', 'hemadeawomanoutofme'],
                ['Silverdome', 'silverdome'],
                ['\'Lude 4', 'lude4'],
                ['Let Bobby', 'letbobby'],
            ],
            'Witches Stew' => [
                ['Miles Run The Voodoo Down', 'milesrunthevoodoodown'],
                ['Sivad', 'sivad'],
                ['Shhh / Peaceful', 'shhhpeaceful'],
                ['It\'s About That Time', 'itsaboutthattime'],
                ['Jean Pierre', 'jeanpierre'],
                ['Black Satin', 'blacksatin'],
                ['Right Off', 'rightoff'],
            ],
            'Elevate' => [
                ['Trapezoid', 'trapezoid'],
                ['Royal Highness', 'royalhighness'],
                ['Krewe', 'krewe'],
                ['Shmink Dabby', 'shminkdabby'],
                ['Everyone Wants to Rule the World', 'everyonewantstoruletheworld'],
                ['Gang Ten', 'gangten'],
                ['Ready to Live', 'readytolive'],
                ['Larimar', 'larimar'],
                ['Love Is Too Strong', 'loveistoostrong'],
                ['Purple Cabbage', 'purplecabbage'],
                ['Trapezoid Dub', 'trapezoiddub'],
            ],
            'Resonate' => [
                ['Blaze', 'blaze'],
                ['Good Morning Mr. Shmink', 'goodmorningmrshmink'],
                ['NDUGU', 'ndugu'],
                ['Checker Wrecker', 'checkerwrecker'],
                ['Silence is Golden', 'silenceisgolden'],
                ['Moksha', 'moksha'],
                ['Mr. Dynamite', 'mrdynamite'],
                ['Remember the Children', 'rememberthechildren'],
                ['\'Lude', 'ludereѕonate'],
                ['House of Lett', 'houseoflett'],
                ['Resonate', 'resonate'],
            ],
            'Unify' => [
                ['RVA Dance', 'rvadance'],
                ['Keep that Funk Alive ft. Bootsy Collins', 'keepthatfunkalive'],
                ['Waffles', 'waffles'],
                ['Everything\'s Gonna Be Alright ft. Nick Daniels', 'everythingsgonnabealright'],
                ['Lude 1', 'lude1unify'],
                ['Hawk\'s Claw', 'hawksclaw'],
                ['The Lock ft. Jeff Lockhart', 'thelock'],
                ['Vámonos', 'vamonos'],
                ['Change the World', 'changetheworld'],
                ['Lude 2', 'lude2unify'],
                ['Gravy Train', 'gravytrain'],
                ['Shine', 'shine'],
                ['Get It Together', 'getittogether'],
                ['Lude 3', 'lude3unify'],
                ['Lett the World Know', 'letttheworldknow'],
                ['Insta-Classic', 'instaclassic'],
            ],
            'Cook' => [
                ['Grewt Up', 'grewtup'],
                ['Clav it Your Way', 'clavityourway'],
                ['Sesshins 1', 'sesshins1'],
                ['7 Tribes', '7tribes'],
                ['Rising to the Top', 'risingtothetop'],
                ['Sesshins 2', 'sesshins2'],
                ['Gold Tooth', 'goldtooth'],
                ['Breathe', 'breathelettuce'],
                ['The Matador', 'thematador'],
                ['Sesshins 3', 'sesshins3'],
                ['Cook', 'cook'],
                ['Storm Coming', 'stormcoming'],
                ['Keep On', 'keepon'],
                ['Sesshins 4', 'sesshins4'],
                ['The Mac', 'themac'],
                ['Ghosts of Yest', 'ghostsofyest'],
            ],
        ],
        'Umphrey\'s McGee' => [
            'Greatest Hits Vol. III' => [
                ['Divisions', 'divisions'],
                ['Kimble', 'kimble'],
                ['Bob', 'bob'],
                ['Phil\'s Farm', 'philsfarm'],
                ['FF', 'ff'],
                ['All in Time', 'allintime'],
                ['Orfeo', 'orfeo'],
                ['August', 'august'],
            ],
            'Local Band Does OK' => [
                ['Andy\'s Last Beer', 'andyslastbeer'],
                ['Uncle Wally', 'unclewally'],
                ['Hurt Bird Bath', 'hurtbirdbath'],
                ['Headphones and Snowcones', 'headphonesandsnowcones'],
                ['Ringo', 'ringo'],
                ['Blue Echo', 'blueecho'],
                ['The Empire Stage', 'theempirestage'],
                ['White Man\'s Moccasins', 'whitemansmoccasins'],
                ['Prowler', 'prowler'],
                ['2nd Self', 'secondself'],
                ['Roulette', 'roulette'],
                ['Dough Bro', 'doughbro'],
                ['Water', 'water'],
                ['Nothing Too Fancy', 'nothingtoofancy'],
            ],
            'Safety in Numbers' => [
                ['Believe The Lie', 'believethelie'],
                ['Rocker', 'rocker'],
                ['Liquid', 'liquid'],
                ['Words', 'words'],
                ['Nemo', 'nemo'],
                ['Women, Wine and Song', 'womenwîneandsong'],
                ['Intentions Clear', 'intentionsclear'],
                ['End of the Road', 'endoftheroad'],
                ['Passing', 'passing'],
                ['Ocean Billy', 'oceanbilly'],
                ['The Weight Around', 'theweightaround'],
            ],
            'Mantis' => [
                ['Made to Measure', 'madetomeasure'],
                ['Preamble', 'preamble'],
                ['Mantis', 'mantis'],
                ['Cemetery Walk', 'cemeterywalk'],
                ['Cemetery Walk II', 'cemeterywalk2'],
                ['Turn & Run', 'turnandrun'],
                ['Spires', 'spires'],
                ['Prophecy Now', 'prophecynow'],
                ['Red Tape', 'redtape'],
                ['1348', 'track1348'],
            ],
            'Death by Stereo' => [
                ['Miami Virtue', 'miamivirtue'],
                ['Domino Theory', 'dominotheory'],
                ['Search 4', 'search4'],
                ['Booth Love', 'boothlove'],
                ['The Floor', 'thefloor'],
                ['Wellwishers', 'wellwishers'],
                ['Dim Sun', 'dimsun'],
                ['Deeper', 'deeper'],
                ['Conduit', 'conduit'],
                ['Hajimemashite', 'hajimemashite'],
            ],
            'Similar Skin' => [
                ['The Linear', 'thelinear'],
                ['Cut the Cable', 'cutthecable'],
                ['Hourglass', 'hourglass'],
                ['No Diablo', 'nodiablo'],
                ['Similar Skin', 'similarskin'],
                ['Puppet String', 'puppetstring'],
                ['Little Gift', 'littlegift'],
                ['Educated Guess', 'educatedguess'],
                ['Loose Ends', 'looseends'],
                ['Hindsight', 'hindsight'],
                ['Bridgeless', 'bridgeless'],
            ],
            'The London Session' => [
                ['Bad Friday', 'badfriday'],
                ['Rocker Part 2', 'rockerpart2'],
                ['No Diablo', 'nodiablolondon'],
                ['Cut the Cable', 'cutthecablelondon'],
                ['Out of Order', 'outoforder'],
                ['Glory', 'glory'],
                ['Plunger', 'plunger'],
                ['Comma Later', 'commalater'],
                ['Eat', 'eat'],
                ['I Want You (She\'s So Heavy)', 'iwantyoushessoheavy'],
            ],
            'ZONKEY' => [
                ['National Loser Anthem', 'nationalloserаnthem'],
                ['Life During Exodus', 'lifeduringexodus'],
                ['Can\'t Rock My Dream Face', 'cantrockmydreamface'],
                ['Sad Clint Eastwood', 'sadclinteastwood'],
                ['Electric Avenue to Hell', 'electricavenuetohell'],
                ['Ace of Long Nights', 'aceoflongnights'],
                ['Sweet Sunglasses', 'sweetsunglasses'],
                ['Strangletage', 'strangletage'],
                ['Come As Your Kids', 'comeasyourkids'],
                ['Frankie Zombie', 'frankiezombie'],
                ['Bulls on the Bus', 'bullsonthebus'],
                ['Bittersweet Haj', 'bittersweethaj'],
            ],
            'it\'s not us' => [
                ['The Silent Type', 'thesilenttype'],
                ['Looks', 'looks'],
                ['Whistle Kids', 'whistlekids'],
                ['Half Delayed', 'halfdelayed'],
                ['Maybe Someday', 'maybesomeday'],
                ['Remind Me', 'remindme'],
                ['You & You Alone', 'youandyoualone'],
                ['Forks', 'forks'],
                ['Speak Up (feat. Joshua Redman)', 'speakup'],
                ['Piranhas', 'piranhas'],
                ['Dark Brush', 'darkbrush'],
            ],
            'it\'s you' => [
                ['Triangle Tear', 'triangletear'],
                ['What We Could Get', 'whatwecouldget'],
                ['Push & Pull', 'pushandpull'],
                ['In The Black', 'intheblack'],
                ['Xmas at Wartime', 'xmasatwartime'],
                ['Seasons', 'seasons'],
                ['Nether', 'nether'],
                ['Hanging Chads', 'hangingchads'],
                ['Attachments', 'attachments'],
                ['Upward', 'upward'],
            ],
            'Asking For a Friend' => [
                ['I Don\'t Know What I Want', 'idontknοwwhatiwant'],
                ['Small Strides', 'smallstrides'],
                ['Always October', 'alwaysoctober'],
                ['Fenced In', 'fencedin'],
                ['New Wings', 'newwings'],
                ['So Much', 'somuch'],
                ['Dayville Monarchy', 'dayvillemonarchy'],
                ['Hiccup', 'hiccup'],
                ['Pure Saturation', 'puresaturation'],
                ['It\'s Not Your Fault', 'itsnotyourfault'],
                ['Escape Goat', 'escapegoat'],
                ['How About Now?', 'howaboutnow'],
                ['Ordinary Times', 'ordinarytimes'],
                ['Work Sauce', 'worksauce'],
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
                        $this->logger->warning(sprintf('Album category not found: %s - %s', $artistName, (string) $albumName));
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
                'AddTracksGroup4 completed. Added %d tracks for %d artists.',
                $totalTracks,
                count(self::TRACKS)
            ));
        } catch (\Exception $e) {
            $this->logger->error('Failed to add tracks (Group 4): ' . $e->getMessage());
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
