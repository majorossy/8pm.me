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
 * Add Tracks Group 5 Data Patch
 *
 * Adds tracks for remaining 12 artists: Warren Zevon, Yonder Mountain String Band,
 * Matisyahu, Leftover Salmon, Rusted Root, God Street Wine, Twiddle,
 * Tedeschi Trucks Band, Cabinet, Dogs in a Pile, Phil Lesh and Friends, Ratdog.
 */
class AddTracksGroup5 implements DataPatchInterface
{
    /**
     * Track data structure
     * Format: Artist Name => Album Name => [[track_name, track_url_key], ...]
     */
    private const TRACKS = [
        'Warren Zevon' => [
            'Wanted Dead or Alive' => [
                ['Wanted Dead or Alive', 'wanteddeadoralive'],
                ['She Quit Me', 'shequitme'],
                ['Hitch Hikin\' Woman', 'hitchhikinwoman'],
                ['A Bullet For Ramona', 'abulletforramona'],
                ['Gorilla', 'gorilla'],
                ['Traveling In The Lightnin\'', 'travelinginthelightnin'],
                ['Tule\'s Blues', 'tulesblues'],
                ['Calcutta', 'calcutta'],
                ['Iko-Iko', 'ikoiko'],
                ['Fiery Emblems', 'fieryemblems'],
            ],
            'Warren Zevon' => [
                ['Frank and Jesse James', 'frankandjessejames'],
                ['Mama Couldn\'t Be Persuaded', 'mamacouldntbepersuaded'],
                ['Backs Turned Looking Down the Path', 'backsturnedlookingdownthepath'],
                ['Hasten Down the Wind', 'hastendownthewind'],
                ['Poor Poor Pitiful Me', 'poorpoorpitifulme'],
                ['The French Inhaler', 'thefrenchinhaler'],
                ['Mohammed\'s Radio', 'mohammedsradio'],
                ['I\'ll Sleep When I\'m Dead', 'illsleepwhenimdead'],
                ['Carmelita', 'carmelita'],
                ['Join Me in L.A.', 'joinmeinla'],
                ['Desperados Under the Eaves', 'desperadosundertheeaves'],
            ],
            'Bad Luck Streak in Dancing School' => [
                ['Bad Luck Streak In Dancing School', 'badluckstreakindancingschool'],
                ['A Certain Girl', 'acertaingirl'],
                ['Jungle Work', 'junglework'],
                ['Empty-Handed Heart', 'emptyhandedheart'],
                ['Interlude No. 1', 'interlude1'],
                ['Play It All Night Long', 'playitallnightlong'],
                ['Jeannie Needs A Shooter', 'jeannieneedsashooter'],
                ['Interlude No. 2', 'interlude2'],
                ['Bill Lee', 'billlee'],
                ['Gorilla, You\'re A Desperado', 'gorillayoureadesperado'],
                ['Bed Of Coals', 'bedofcoals'],
                ['Wild Age', 'wildage'],
            ],
            'The Envoy' => [
                ['The Envoy', 'theenvoy'],
                ['The Overdraft', 'theoverdraft'],
                ['The Hula Hula Boys', 'thehulahulaboys'],
                ['Jesus Mentioned', 'jesusmentioned'],
                ['Let Nothing Come Between You', 'letnothingcomebetweenyou'],
                ['Ain\'t That Pretty At All', 'aintthatprettyatall'],
                ['Charlie\'s Medicine', 'charliesmedicine'],
                ['Looking For The Next Best Thing', 'lookingforthenextbestthing'],
                ['Never Too Late For Love', 'nevertoolateforlove'],
            ],
            'Sentimental Hygiene' => [
                ['Sentimental Hygiene', 'sentimentalhygiene'],
                ['Boom Boom Mancini', 'boomboommancini'],
                ['The Factory', 'thefactory'],
                ['Trouble Waiting To Happen', 'troublewaitingtohappen'],
                ['Reconsider Me', 'reconsiderme'],
                ['Detox Mansion', 'detoxmansion'],
                ['Bad Karma', 'badkarma'],
                ['Even A Dog Can Shake Hands', 'evenadogcanshakehands'],
                ['The Heartache', 'theheartache'],
                ['Leave My Monkey Alone', 'leavemymonkeyalone'],
            ],
            'Transverse City' => [
                ['Transverse City (feat. Jerry Garcia)', 'transversecity'],
                ['Run Straight Down', 'runstraightdown'],
                ['The Long Arm of the Law', 'thelongarmofthelaw'],
                ['Turbulence', 'turbulence'],
                ['They Moved the Moon', 'theymovedthemoon'],
                ['Splendid Isolation', 'splendidisolation'],
                ['Networking', 'networking'],
                ['Down in the Mall', 'downinthemall'],
                ['Gridlock', 'gridlock'],
                ['Nobody\'s in Love This Year', 'nobodysinlovethisyear'],
            ],
            'Mr. Bad Example' => [
                ['Finishing Touches', 'finishingtouches'],
                ['Suzie Lightning', 'suzielightning'],
                ['Model Citizen', 'modelcitizen'],
                ['Angel Dressed in Black', 'angeldressedinblack'],
                ['Mr. Bad Example', 'mrbadexample'],
                ['Renegade', 'renegade'],
                ['Heartache Spoken Here', 'heartachespokenhere'],
                ['Quite Ugly One Morning', 'quiteuglyonemorning'],
                ['Things to Do in Denver When You\'re Dead', 'thingstodoindenverwhenyouredead'],
                ['Searching for a Heart', 'searchingforaheart'],
            ],
            'Mutineer' => [
                ['Seminole Bingo', 'seminolebingo'],
                ['Something Bad Happened to a Clown', 'somethingbadhappenedtoaclown'],
                ['Similar to Rain', 'similartorain'],
                ['The Indifference of Heaven', 'theindifferenceofheaven'],
                ['Jesus Was a Cross Maker', 'jesuswasacrossmaker'],
                ['Poisonous Lookalike', 'poisonouslookalike'],
                ['Piano Fighter', 'pianofighter'],
                ['Rottweiler Blues', 'rottweilerblues'],
                ['Monkey Wash Donkey Rinse', 'monkeywashdonkeyrinse'],
                ['Mutineer', 'mutineer'],
            ],
            'Life\'ll Kill Ya' => [
                ['I Was in the House When the House Burned Down', 'iwasinthehousewhenthehouseburneddown'],
                ['Life\'ll Kill Ya', 'lifellkillyа'],
                ['Porcelain Monkey', 'porcelainmonkey'],
                ['For My Next Trick I\'ll Need a Volunteer', 'formynexttrickillneedavolunteer'],
                ['I\'ll Slow You Down', 'illslowyoudown'],
                ['Hostage-O', 'hostageo'],
                ['Dirty Little Religion', 'dirtylittlereligion'],
                ['Back in the High Life Again', 'backinthehighlifeagain'],
                ['My Shit\'s Fucked Up', 'myshitsfuckedup'],
                ['Fistful of Rain', 'fistfulofrain'],
                ['Ourselves to Know', 'ourselvestoknow'],
                ['Don\'t Let Us Get Sick', 'dontletusηgetsick'],
            ],
            'My Ride\'s Here' => [
                ['Sacrificial Lambs', 'sacrificiallambs'],
                ['Basket Case', 'basketcase'],
                ['Lord Byron\'s Luggage', 'lordbyronsluggage'],
                ['Macgillycuddy\'s Reeks', 'macgillycuddysreeks'],
                ['You\'re A Whole Different Person When You\'re Scared', 'youreaωholedifferentpersonwhenyourescared'],
                ['Hit Somebody! (The Hockey Song)', 'hitsomebodythehockeysong'],
                ['Genius', 'genius'],
                ['My Ride\'s Here', 'myrideshere'],
                ['Laissez-Moi Tranquille', 'laissezmoitranquille'],
                ['I Have to Leave', 'ihavetoleave'],
            ],
            'The Wind' => [
                ['Dirty Life And Times', 'dirtylifeandtimes'],
                ['Disorder In The House', 'disorderinthehouse'],
                ['Knockin\' On Heaven\'s Door', 'knockinonheavensdoor'],
                ['Numb As A Statue', 'numbasastatue'],
                ['She\'s Too Good For Me', 'shestoοgoodforme'],
                ['Prison Grove', 'prisongrove'],
                ['El Amor De Mi Vida', 'elamordemivida'],
                ['The Rest Of The Night', 'therestofthenight'],
                ['Please Stay', 'pleasestay'],
                ['Rub Me Raw', 'rubmeraw'],
                ['Keep Me In Your Heart', 'keepmeinyourheart'],
            ],
        ],
        'Yonder Mountain String Band' => [
            'Elevation' => [
                ['Half Moon Rising', 'halfmoonrising'],
                ['Mental Breakdown', 'mentalbreakdown'],
                ['The Bolton Stretch', 'theboltonstretch'],
                ['Left Me in a Hole', 'leftmeinahole'],
                ['Darkness and Light', 'darknessandlight'],
                ['On the Run', 'ontherun'],
                ['Eight Cylinders', 'eightcylinders'],
                ['40 Miles from Denver', 'fortymilesπfromdenver'],
                ['This Lonesome Heart', 'thislonesomeheart'],
                ['At the End of the Day', 'attheendoftheday'],
                ['Mossy Cow', 'mossycow'],
                ['High on a Hilltop', 'highonahilltop'],
                ['To Say Goodbye, to Be Forgiven', 'tosaygoodbyetobeforgiven'],
                ['If There\'s Still Ramblin\' in the Rambler (Let Him Go)', 'iftheresstillramblinintherambletlethimgo'],
                ['Waijal Breakdown', 'waijalbreakdown'],
            ],
            'Town By Town' => [
                ['Rambler\'s Anthem', 'ramblersanthem'],
                ['Easy as Pie', 'easyaspie'],
                ['Idaho', 'idaho'],
                ['Loved You Enough', 'lovedyouenough'],
                ['Sorrow Is a Highway', 'sorrowisahighway'],
                ['Must\'ve Had Your Reasons', 'mustvehadyourreasons'],
                ['Wildewood Drive', 'wildewooddrive'],
                ['New Horizons', 'newhorizons'],
                ['Check out Time', 'checkouttime'],
                ['To See You Comin\' Round The Bend', 'toseeyoucomινroundthebend'],
                ['Red Tail Lights', 'redtaillights'],
                ['A Father\'s Arms', 'afathersarms'],
                ['Hog Potato', 'hogpotato'],
                ['Peace of Mind', 'peaceofmind'],
            ],
            'Mountain Tracks: Volume 2' => [
                ['At the End of the Day', 'attheendoftheday'],
                ['Dawn\'s Early Light', 'dawnsearlylight'],
                ['Two Hits and the Joint Turned Brown', 'twohitsandthejointturnedbrown'],
                ['Raleigh and Spencer', 'raleighandspencer'],
                ['Good Hearted Woman', 'goodheartedwoman'],
                ['No Expectations', 'noexpectations'],
                ['Peace of Mind / Follow Me Down to the Riverside / Peace of Mind', 'peaceofmindfollowmedowntotheriverside'],
                ['Untitled (Goodbye Blue Sky)', 'untitledgoodbyebluesky'],
            ],
            'Yonder Mountain String Band' => [
                ['Sidewalk Stars', 'sidewalkstars'],
                ['I Ain\'t Been Myself In Years', 'iaintbeenmyselfinyears'],
                ['How \'Bout You?', 'howboutyou'],
                ['Angel', 'angel'],
                ['Fastball', 'fastball'],
                ['East Nashville Easter', 'eastnashvilleeaster'],
                ['Just The Same', 'justthesame'],
                ['Classic Situation', 'classicsituation'],
                ['Night Out', 'nightout'],
                ['Midwest Gospel Radio', 'midwestgospelradio'],
                ['Troubled Mind', 'troubledmind'],
                ['Wind\'s On Fire', 'windsonfire'],
            ],
            'The Show' => [
                ['Out of the Blue', 'outoftheblue'],
                ['Complicated', 'complicated'],
                ['Fingerprint', 'fingerprint'],
                ['Dreams', 'dreams'],
                ['Honestly', 'honestly'],
                ['Steep Grade, Sharp Curves', 'steepgradesharpcurves'],
                ['Isolate', 'isolate'],
                ['In The Seam', 'intheseam'],
                ['Belle Parker', 'belleparker'],
                ['Criminal', 'criminal'],
                ['Rain Still Falls', 'rainstillfalls'],
                ['Fine Excuses', 'fineexcuses'],
                ['Casualty', 'casualty'],
            ],
            'Black Sheep' => [
                ['Insult and Elbow', 'insultandelbow'],
                ['Black Sheep', 'blacksheep'],
                ['Ever Fallen In Love', 'everfalleninlove'],
                ['Annalee', 'annalee'],
                ['Landfall', 'landfall'],
                ['I\'m Lost', 'imlost'],
                ['Around You', 'aroundyou'],
                ['Love Before You Can\'t', 'lovebeforeyoucant'],
                ['Drawing a Melody', 'drawingamelody'],
                ['New Dusty Miller', 'newdustymiller'],
            ],
            'Love. Ain\'t Love' => [
                ['Allison', 'allison'],
                ['Bad Taste', 'badtaste'],
                ['Take a Chance on Me', 'takeachanceonme'],
                ['Chasing My Tail', 'chasingmytail'],
                ['Used To It', 'usedtoit'],
                ['Eat In. Go Deaf (Eat Out. Go Broke)', 'eatingodeaf'],
                ['Dancin\' In The Moonlight', 'dancininthemoonlight'],
                ['Kobe The Dog', 'kobethedog'],
                ['Last Of The Railroad Men', 'lastoftherailroadmen'],
                ['Up For Brinkleys', 'upforbrinkleys'],
                ['Groovin\' Away', 'groovinaway'],
            ],
            'Get Yourself Outside' => [
                ['Beside Myself', 'besidemyself'],
                ['I Just Can\'t', 'ijustcant'],
                ['Small House', 'smallhouse'],
                ['If Only', 'ifonly'],
                ['Up This Hill', 'upthishill'],
                ['No Leg Left', 'nolegleft'],
                ['Out Of The Pan', 'outofthepan'],
                ['Into the Fire', 'intothefire'],
                ['Broken Records', 'brokenrecords'],
                ['Change of Heart', 'changeofheart'],
                ['Suburban Girl', 'suburbangirl'],
            ],
            'Nowhere Next' => [
                ['The Truth Fits', 'thetruthfits'],
                ['Crusin\'', 'crusin'],
                ['Here I Go (featuring Jerry Douglas)', 'hereigo'],
                ['Didn\'t Go Wrong (featuring Jerry Douglas)', 'didntgowrong'],
                ['Nowhere Next', 'nowherenext'],
                ['Leave The Midwest', 'leavethemidwest'],
                ['Secondhand Smoke', 'secondhandsmoke'],
                ['Come See Me', 'comeseeme'],
                ['Outlaw', 'outlaw'],
                ['Wasting Time (featuring Jerry Douglas)', 'wastingtime'],
                ['River', 'river'],
            ],
        ],
        'Matisyahu' => [
            'Shake Off the Dust... Arise' => [
                ['Chop \'em Down', 'chopemdown'],
                ['Tzama L\'chol Nafshi', 'tzamalcholnafshi'],
                ['Got No Water', 'gotnowater'],
                ['King Without A Crown', 'kingwithoutacrown'],
                ['Interlude', 'interlude1'],
                ['Father In The Forest', 'fatherintheforest'],
                ['Interlude', 'interlude2'],
                ['Aish Tamid', 'aishtamid'],
                ['Short Nigun', 'shortnigun'],
                ['Candle', 'candle'],
                ['Close My Eyes', 'closemyeyes'],
                ['Interlude', 'interlude3'],
                ['Exaltation', 'exaltation'],
                ['Refuge', 'refuge'],
                ['Interlude', 'interlude4'],
                ['Warrior', 'warrior'],
                ['Outro', 'outro'],
            ],
            'Youth' => [
                ['Fire Of Heaven/Altar Of Earth', 'fireofheavenaltarofearth'],
                ['Youth', 'youth'],
                ['Time Of Your Song', 'timeofyoursong'],
                ['Dispatch the Troops', 'dispatchthetroops'],
                ['Indestructible', 'indestructible'],
                ['What I\'m Fighting For', 'whatimfightingfor'],
                ['Jerusalem', 'jerusalem'],
                ['WP', 'wp'],
                ['Shalom/Salaam', 'shalomsalaam'],
                ['Late Night In Zion', 'latenightinzion'],
                ['Unique Is My Dove', 'uniqueismydove'],
                ['Ancient Lullaby', 'ancientlullaby'],
                ['King Without A Crown', 'kingwithoutacrown'],
                ['King Without A Crown (Mike D remix)', 'kingwithoutacrownremix'],
            ],
            'Light' => [
                ['Smash Lies', 'smashlies'],
                ['We Will Walk', 'wewillwalk'],
                ['One Day', 'oneday'],
                ['Escape', 'escape'],
                ['So Hi So Lo', 'sohisolo'],
                ['I Will Be Light', 'iwillbelight'],
                ['For You', 'foryou'],
                ['On Nature', 'onnature'],
                ['Motivate', 'motivate'],
                ['Struggla', 'struggla'],
                ['Darkness Into Light', 'darknessintolight'],
                ['Thunder', 'thunder'],
                ['Silence', 'silence'],
            ],
            'Spark Seeker' => [
                ['Crossroads (feat. J. Ralph)', 'crossroads'],
                ['Sunshine', 'sunshine'],
                ['Searchin', 'searchin'],
                ['Buffalo Soldier (feat. Shyne)', 'buffalosoldier'],
                ['Fire of Freedom', 'fireoffreedom'],
                ['Bal Shem Tov', 'balshemtov'],
                ['I Believe In Love', 'ibelieveinlove'],
                ['Breathe Easy', 'breatheeasy'],
                ['Summer Wind', 'summerwind'],
                ['Live Like a Warrior', 'livelikeawarrior'],
                ['Tel Aviv\'n', 'telavivin'],
                ['King Crown of Judah (feat. Shyne and Ravid Kahalani)', 'kingcrownofjudah'],
                ['Shine on You', 'shineonyou'],
            ],
            'Akeda' => [
                ['Reservoir', 'reservoir'],
                ['Broken Car', 'brokencar'],
                ['Watch The Walls Melt Down', 'watchthewallsmeltdown'],
                ['Champion', 'champion'],
                ['Built To Survive (feat. Zion I)', 'builttosurvive'],
                ['Ayeka (Teach Me To Love)', 'ayekateachmetolove'],
                ['Black Heart', 'blackheart'],
                ['Star On The Rise', 'starontherise'],
                ['Surrender', 'surrender'],
                ['Confidence (feat. Collie Buddz)', 'confidence'],
                ['Vow of Silence', 'vowofsilence'],
                ['Obstacles', 'obstacles'],
                ['Hard Way', 'hardway'],
                ['Sick For So Long', 'sickforsolong'],
                ['Akeda', 'akeda'],
            ],
            'Undercurrent' => [
                ['Step Out Into the Light', 'stepoutintothelight'],
                ['Back to the Old', 'backtotheold'],
                ['Coming Up Empty', 'comingupempty'],
                ['Bsp: Blue Sky Playground', 'bspblueskyplayground'],
                ['Tell Me', 'tellme'],
                ['Forest of Faith', 'forestoffaith'],
                ['Head Right', 'headright'],
                ['Driftin\'', 'driftin'],
            ],
            'Matisyahu' => [
                ['Not Regular', 'notregular'],
                ['AM_RICA', 'america'],
                ['Chameleon (feat. Salt Cathedral)', 'chameleon'],
                ['Keep Coming Back for More (feat. Salt Cathedral)', 'keepcomingbackformore'],
                ['Mama Please Don\'t Worry', 'mamapleasedontworry'],
                ['In My Mind', 'inmymind'],
                ['Music is the Anthem', 'musicistheanthem'],
                ['Got to See It All', 'gottoηseeitall'],
                ['Lonely Day', 'lonelyday'],
                ['Tugboat', 'tugboat'],
                ['Flip It Fantastic', 'flipitfantastic'],
                ['When the Smoke Clears', 'whenthesmokeclears'],
                ['Raindance', 'raindance'],
            ],
            'Ancient Child' => [
                ['Pro-cess', 'process'],
                ['Anxiety (feat. BLP Kosher)', 'anxiety'],
                ['Sound Foundation', 'soundfoundation'],
                ['Crosswinds', 'crosswinds'],
                ['Son Come Up (feat. LAIVY)', 'soncomeup'],
                ['Rockets (feat. Duvbear)', 'rockets'],
                ['Find A Way', 'findaway'],
                ['Smoke And Mirrors', 'smokeandmirrors'],
                ['Wake Up', 'wakeup'],
                ['Balance', 'balance'],
                ['Ritual', 'ritual'],
                ['Rockin Tempos (feat. Duvbear)', 'rockintempos'],
            ],
        ],
        'Leftover Salmon' => [
            'Bridges to Bert' => [
                ['Booboo/Lord Melody', 'booboolordmelody'],
                ['Going Through The Motions', 'goingthroughthemotions'],
                ['Head Bag', 'headbag'],
                ['Whiskey Before Breakfast/Over The Waterfall', 'whiskeybeforebreakfast'],
                ['Bridges Of Time', 'bridgesoftime'],
                ['Nothing But Time', 'nothingbuttime'],
                ['Tu N\'As Pas Aller', 'tunaspasaller'],
                ['Pasta On The Mountain', 'pastaonthemountain'],
                ['Just Before Evening', 'justbeforeevening'],
                ['Dark Eyes', 'darkeyes'],
                ['Zombie Jamboree', 'zombiejamboree'],
                ['Bosco Stumble', 'boscostumble'],
                ['Rodeo Geek', 'rodeogeek'],
            ],
            'Euphoria' => [
                ['Better', 'better'],
                ['Highway Song', 'highwaysong'],
                ['Baby Hold On', 'babyholdon'],
                ['River\'s Rising', 'riversrising'],
                ['Mama Boulet', 'mamaboulet'],
                ['Funky Mountain Fogdown', 'funkymountainfogdown'],
                ['Cash on the Barrelhead', 'cashonthebarrelhead'],
                ['Muddy Water Home', 'muddywaterhome'],
                ['Ain\'t Gonna Work', 'aintgonnawork'],
                ['This is the Time', 'thisisthetime'],
                ['Euphoria', 'euphoria'],
            ],
            'The Nashville Sessions' => [
                ['Midnight Blues (feat. Del McCoury & Ronnie McCoury)', 'midnightblues'],
                ['Lovin\' in My Baby\'s Eyes (feat. Taj Mahal & Sally Van Meter)', 'lovinmybabyseyes'],
                ['Dance on Your Head (feat. Béla Fleck & Reese Wynans)', 'danceonyourhead'],
                ['Are You Sure Hank Done It This Way (feat. Sally Van Meter, Waylon Jennings, Sam Bush)', 'areyousurehankdoneitthisway'],
                ['Five Alive (feat. Earl Scruggs)', 'fivealive'],
                ['Breakin\' Thru (feat. Reese Wynans, Jerry Douglas, John Cowan)', 'breakinthru'],
                ['Lines Around Your Eyes (feat. Lucinda Williams & Jo-El Sonnier)', 'linesaroundyoureyes'],
                ['Another Way To Turn', 'anotherwaytoturn'],
                ['Up On The Hill Where We Do The Boogie', 'uponthehillwherewedoboogie'],
                ['It\'s Your World', 'itsyourworld'],
                ['Troubled Times', 'troubledtimes'],
                ['On The Other Side', 'ontheotherside'],
                ['Nobody\'s Fault But Mine', 'nobodysfaultbutmine'],
            ],
            'Leftover Salmon' => [
                ['Down In the Hollow', 'downinthehollow'],
                ['Mountain Top', 'mountaintop'],
                ['Delta Queen', 'deltaqueen'],
                ['Lincoln At Nevada', 'lincolnatnevada'],
                ['Woody Guthrie', 'woodyguthrie'],
                ['Fayetteville Line', 'fayettevilleline'],
                ['Everything Is Round', 'everythingisround'],
                ['Whispering Waters', 'whisperingwaters'],
                ['Last Days Of Autumn', 'lastdaysofautumn'],
                ['Just Keep Walkin\'', 'justkeepwalkin'],
                ['Weary Traveler', 'wearytraveler'],
            ],
            'Aquatic Hitchhiker' => [
                ['Gulf of Mexico', 'gulfofmexico'],
                ['Keep Driving', 'keepdriving'],
                ['Liza', 'liza'],
                ['Aquatic Hitchhiker', 'aquatichitchhiker'],
                ['Bayou Town', 'bayoutown'],
                ['Sing Up to the Moon', 'singuptothemoon'],
                ['Light Behind the Rain', 'lightbehindtherain'],
                ['Stop All Your Worrying', 'stopallyourworrying'],
                ['Walking Shoes', 'walkingshoes'],
                ['Kentucky Skies', 'kentuckyskies'],
                ['Gone for Long', 'goneforlong'],
                ['Here Comes the Night', 'herecomesthenight'],
            ],
            'High Country' => [
                ['Get up and Go', 'getupandgo'],
                ['Western Skies', 'westernskies'],
                ['Home Cookin\'', 'homecookin'],
                ['High Country', 'highcountry'],
                ['Bluegrass Pines', 'bluegrasspines'],
                ['Better Day', 'betterday'],
                ['Six Feet of Snow', 'sixfeetofsnow'],
                ['So Lonesome', 'solonesome'],
                ['Light in the Woods', 'lightinthewoods'],
                ['Thornpipe', 'thornpipe'],
                ['Two Highways', 'twohighways'],
                ['Finish Your Beer', 'finishyourbeer'],
            ],
            'Something Higher' => [
                ['Places', 'places'],
                ['Show Me Something Higher', 'showmesomethinghigher'],
                ['Southern Belle', 'southernbelle'],
                ['Analog', 'analog'],
                ['House Of Cards', 'houseofcards'],
                ['Evermore', 'evermore'],
                ['Astral Traveler', 'astraltraveler'],
                ['Foreign Fields', 'foreignfields'],
                ['Game Of Thorns', 'gameofthorns'],
                ['Let In A Little Light', 'letinalittlelight'],
                ['Winter\'s Gone', 'wintersgone'],
                ['Burdened Heart', 'burdenedheart'],
            ],
            'Brand New Good Old Days' => [
                ['Waterfront', 'waterfront'],
                ['Black Hole Sun', 'blackholesun'],
                ['We\'ll Get By', 'wellgetby'],
                ['Red Fox Run', 'redfoxrun'],
                ['Flyin\' at Night', 'flyinatnight'],
                ['Left Unsung', 'leftunsung'],
                ['Category Stomp', 'categorystomp'],
                ['Sunday', 'sunday'],
                ['Boogie Grass Band', 'boogiegrassband'],
                ['Brand New Good Old Days', 'brandnewgoodolddays'],
            ],
        ],
        'Rusted Root' => [
            'Cruel Sun' => [
                ['Primal Scream', 'primalscream'],
                ['Send Me On My Way', 'sendmeonmyway'],
                ['Tree', 'tree'],
                ['Won\'t Be Long', 'wontbelong'],
                ['!@#*', 'specialchars'],
                ['Cat Turned Blue', 'catturnedblue'],
                ['Artificial Winter', 'artificialwinter'],
                ['Where She Runs', 'wheresheruns'],
                ['Martyr', 'martyr'],
                ['Back To The Earth', 'backtotheearth'],
                ['Scattered', 'scattered'],
            ],
            'When I Woke' => [
                ['Drum Trip', 'drumtrip'],
                ['Ecstasy', 'ecstasy'],
                ['Send Me On My Way', 'sendmeonmyway'],
                ['Cruel Sun', 'cruelsun'],
                ['Cat Turned Blue', 'catturnedblue'],
                ['Beautiful People', 'beautifulpeople'],
                ['Martyr', 'martyr'],
                ['Rain', 'rain'],
                ['Food & Creative Love', 'foodcreativelove'],
                ['Lost In A Crowd', 'lostinacrowd'],
                ['Laugh As The Sun', 'laughasthesun'],
                ['River in a Cage', 'riverinacage'],
                ['Flight', 'flight'],
            ],
            'Remember' => [
                ['Faith I Do Believe', 'faithidobelieve'],
                ['Heaven', 'heaven'],
                ['Sister Contine', 'sistercontine'],
                ['Virtual Reality', 'virtualreality'],
                ['Infinite Space', 'infinitespace'],
                ['Voodoo', 'voodoo'],
                ['Dangle', 'dangle'],
                ['Silver-n-Gold', 'silverngold'],
                ['Baby Will Raam', 'babywillraam'],
                ['Bullets in the Fire', 'bulletsinthefire'],
                ['Who Do You Tell It To', 'whodoyoutellitto'],
                ['River in a Cage', 'riverinacage'],
                ['Scattered', 'scattered'],
                ['Circle of Remembrance', 'circleofrememηbrance'],
            ],
            'Rusted Root' => [
                ['She Roll Me Up', 'sherollmeup'],
                ['Rising Sun', 'risingsun'],
                ['Magenta Radio', 'magentaradio'],
                ['My Love', 'mylove'],
                ['Live a Long Time', 'livealongtime'],
                ['Kill You Dead', 'killyoudead'],
                ['Airplane', 'airplane'],
                ['Agbadza', 'agbadza'],
                ['Moon', 'moon'],
                ['Away From', 'awayfrom'],
                ['Flower', 'flower'],
                ['You Can\'t Always Get What You Want', 'youcantalwaysgetwhatyouwant'],
            ],
            'Welcome to My Party' => [
                ['Union 7', 'union7'],
                ['Welcome to My Party', 'welcometomyparty'],
                ['Women Got My Money', 'womengotmymoney'],
                ['Blue Diamonds', 'bluediamonds'],
                ['Weave', 'weave'],
                ['Artificial Winter', 'artificialwinter'],
                ['Too Much', 'toomuch'],
                ['Sweet Mary', 'sweetmary'],
                ['Hands Are Law', 'handsarelaw'],
                ['Cry', 'cry'],
                ['People of My Village', 'peopleofmyvillage'],
            ],
            'Stereo Rodeo' => [
                ['Dance In The Middle', 'danceinthemiddle'],
                ['Suspicious Minds', 'suspiciousminds'],
                ['Weary Bones', 'wearybones'],
                ['Bad Son', 'badson'],
                ['Give Grace', 'givegrace'],
                ['Driving One', 'drivingone'],
                ['Stereo Rodeo', 'stereorodeo'],
                ['Driving Two', 'drivingtwo'],
                ['Animals Love Touch', 'animalslovetouch'],
                ['Garbage Man', 'garbageman'],
                ['Crucible Glow', 'crucibleglow'],
                ['You Can Bet', 'youcanbet'],
            ],
            'The Movement' => [
                ['Monkey Pants', 'monkeypants'],
                ['Cover Me Up', 'covermeup'],
                ['The Movement', 'themovement'],
                ['In Our Sun', 'inoursun'],
                ['Fossil Man', 'fossilman'],
                ['Fortunate Freaks', 'fortunatefreaks'],
                ['Sun and Magic', 'sunandmagic'],
                ['Up and All Around', 'upandallaround'],
                ['Something\'s on My Mind', 'somethingsonmymind'],
                ['Up and All Around (Live)', 'upandallaroundlive'],
            ],
        ],
        'God Street Wine' => [
            'Bag' => [
                ['Nightingale', 'nightingale'],
                ['Goodnight Gretchen', 'goodnightgretchen'],
                ['Waiting For The Tide', 'waitingforthetide'],
                ['Feel The Pressure', 'feelthepressure'],
                ['Fortress Of Solitude', 'fortressofsolitude'],
                ['Upside Down & Inside Out', 'upsidedowninsideout'],
                ['Borderline', 'borderline'],
                ['Hellfire', 'hellfire'],
                ['Better Than You', 'betterthanyou'],
                ['One-Armed Man', 'onearmedman'],
                ['Home Again', 'homeagain'],
                ['Epilog', 'epilog'],
            ],
            '$1.99 Romances' => [
                ['Princess Henrietta', 'princesshenrietta'],
                ['Mile By Mile', 'milebymile'],
                ['Nightingale', 'nightingale'],
                ['Thirsty', 'thirsty'],
                ['Stone House', 'stonehouse'],
                ['Molly', 'molly'],
                ['The Ballroom', 'theballroom'],
                ['Run To You', 'runtoyou'],
                ['Crazy Head', 'crazyhead'],
                ['Hammer and a Spike', 'hammerandaspike'],
                ['Wendy', 'wendy'],
                ['Imogene', 'imogene'],
                ['Tina\'s Town', 'tinastown'],
                ['Into the Sea', 'intothesea'],
            ],
            'Red' => [
                ['Get on the Train', 'getonthetrain'],
                ['Red & Milky White', 'redmilkywhite'],
                ['Ru4 Real?', 'ru4real'],
                ['Which Way Will She Go?', 'whichwaywillshego'],
                ['Chop!', 'chop'],
                ['Don\'t Tell God', 'donttellgod'],
                ['Girl on Fire', 'girlonfire'],
                ['Maybe', 'maybe'],
                ['Made of Blood', 'madeofblood'],
                ['When the White Sun Turns to Red', 'whenthewhitesunturηnstored'],
                ['Untitled Take Two', 'untitledtaketwo'],
            ],
            'Who\'s Driving?' => [
                ['Snake Eyes', 'snakeeyes'],
                ['Driving West', 'drivingwest'],
                ['Stranger', 'stranger'],
                ['Hollow Frog', 'hollowfrog'],
                ['Imogene', 'imogene'],
                ['Crashing Down', 'crashingdown'],
                ['Feel The Pressure', 'feelthepressure'],
                ['Hellfire', 'hellfire'],
            ],
            'Hot! Sweet! and Juicy!' => [
                ['Burger Special', 'burgerspecial'],
                ['Beautiful Lies', 'beautifullies'],
                ['Nadine', 'nadine'],
                ['Souvenir', 'souvenir'],
                ['Slinky', 'slinky'],
                ['8 Ball', 'eightball'],
                ['When the Melody Plays', 'whenthemelodyplays'],
                ['Spoonful Of Sugar', 'spoonfulofsugar'],
                ['My Good Side', 'mygoodside'],
                ['All Systems Clear', 'allsystemsclear'],
                ['Straight Line', 'straightline'],
                ['You Know Me Best', 'youknowmebest'],
                ['Epiphany', 'epiphany'],
                ['When She Go', 'whenshego'],
                ['Maybe I\'m Amazed', 'maybeiamamazed'],
                ['Faith On Parade', 'faithonparade'],
            ],
        ],
        'Twiddle' => [
            'The Natural Evolution of Consciousness' => [
                ['The Catapillar', 'thecatapillar'],
                ['Carter Candlestick', 'cartercandlestick'],
                ['Orderly Chaos', 'orderlychaos'],
                ['Subconscious Prelude', 'subconsciousprelude'],
                ['Less Than Ostentatious', 'lessthanostentatious'],
                ['Tiberius', 'tiberius'],
                ['Brick of Barley', 'brickofbarley'],
                ['Jamflowman', 'jamflowman'],
                ['Frankenfoote', 'frankenfoote'],
                ['Grandpa Fox', 'grandpafox'],
            ],
            'Somewhere on the Mountain' => [
                ['Daydream Farmer', 'daydreamfarmer'],
                ['Doinkinbonk!!!', 'doinkinbonk'],
                ['Apples', 'apples'],
                ['Beehop', 'beehop'],
                ['When It Rains It Poors', 'whenіitrainsitpoors'],
                ['Wescotton Candy', 'wescottoncandy'],
                ['Second Wind', 'secondwind'],
                ['The Box', 'thebox'],
                ['Hattibagen McRat', 'hattibagenmcrat'],
                ['Wasabi Eruption', 'wasabieruption'],
                ['Honeyburste', 'honeyburste'],
                ['Beethoven and Greene', 'beethovenandgreene'],
            ],
            'PLUMP Chapter One' => [
                ['Complacent Race', 'complacentrace'],
                ['Amydst The Myst', 'amydstthemyst'],
                ['Lost In The Cold', 'lostinthecold'],
                ['Every Soul (ft. Todd Stoops)', 'everysoul'],
                ['Five', 'five'],
                ['Syncopated Healing', 'syncopatedhealing'],
                ['Dusk Till Dawn', 'dusktilldawn'],
                ['Polluted Beauty', 'pollutedbeauty'],
                ['Be There (ft. Kenny Brooks)', 'bethere'],
                ['Indigo Trigger', 'indigotrigger'],
                ['White Light', 'whitelight'],
            ],
            'Plump, Chapters 1 & 2' => [
                ['When It Rains It Poors', 'whenitjrainsitpoors'],
                ['Amydst the Myst', 'amydstthemyst'],
                ['Complacent Race', 'complacentrace'],
                ['Lost In the Cold', 'lostinthecold'],
                ['Five (Radio)', 'fiveradio'],
                ['Every Soul', 'everysoul'],
                ['Syncopated Healing', 'syncopatedhealing'],
                ['Dusk \'til Dawn', 'dusktilldawn'],
                ['Polluted Beauty', 'pollutedbeauty'],
                ['Be There', 'bethere'],
                ['Indigo Trigger', 'indigotrigger'],
                ['White Light', 'whitelight'],
                ['Five (Album Version)', 'fivealbum'],
                ['Enter', 'enter'],
                ['Orlando\'s', 'orlandos'],
                ['Juggernaut', 'juggernaut'],
                ['Moments', 'moments'],
                ['Milk', 'milk'],
                ['Nicodemus Portulay', 'nicodemusportulay'],
                ['New Sun', 'newsun'],
                ['Forevers', 'forevers'],
                ['The Fantastic Tale of Ricky Snickle', 'thefantastictaleofrickysnickle'],
                ['Peas and Carrots', 'peasandcarrots'],
                ['Drifter', 'drifter'],
                ['Blunderbuss', 'blunderbuss'],
                ['Fat Country Baby', 'fatcountrybaby'],
                ['Dinner Fork', 'dinnerfork'],
                ['Purple Forest', 'purpleforest'],
            ],
            'Every Last Leaf' => [
                ['Every Last Leaf I', 'everylastleaf1'],
                ['Beautiful', 'beautiful'],
                ['Distance Makes The Heart (feat. Anders Beck)', 'distancemakestheheart'],
                ['River Drift', 'riverdrift'],
                ['The Mission', 'themission'],
                ['Meant To Be', 'meanttobe'],
                ['Do It Now', 'doitnow'],
                ['Fighting For', 'fightingfor'],
                ['The Devil (feat. John Popper)', 'thedevil'],
                ['Life Back Now', 'lifebacknow'],
                ['Collective Pulse', 'collectivepulse'],
                ['Inside', 'inside'],
                ['Slippin\' In The Kitchen', 'slippinthekitchen'],
                ['Mushrooms Of The Sea', 'mushroomsofthesea'],
                ['Every Last Leaf II', 'everylastleaf2'],
            ],
        ],
        'Tedeschi Trucks Band' => [
            'Revelator' => [
                ['Come See About Me', 'comeseeaboutme'],
                ['Don\'t Let Me Slide', 'dontletmeslide'],
                ['Midnight In Harlem', 'midnightinharlem'],
                ['Bound For Glory', 'boundforglory'],
                ['Simple Things', 'simplethings'],
                ['Until You Remember', 'untilyouremember'],
                ['Ball And Chain', 'ballandchain'],
                ['These Walls', 'thesewalls'],
                ['Learn How To Love', 'learnhowtolove'],
                ['Shrimp And Grits (Interlude)', 'shrimpandgrits'],
                ['Love Has Something Else To Say', 'lovehassomethingelsetosay'],
                ['Shelter', 'shelter'],
            ],
            'Let Me Get By' => [
                ['Anyhow', 'anyhow'],
                ['Laugh About It', 'laughaboutit'],
                ['Don\'t Know What It Means', 'dontknowwhatitmeans'],
                ['Right On Time', 'rightontime'],
                ['Let Me Get By', 'letmegetby'],
                ['Just As Strange', 'justasstrange'],
                ['Crying Over You / Swamp Raga for Hozapfel, Lefebvre, Flute and Harmonium', 'cryingoveryou'],
                ['Hear Me', 'hearme'],
                ['I Want More', 'iwantmore'],
                ['In Every Heart', 'ineveryheart'],
            ],
            'Signs' => [
                ['Signs (High Times)', 'signshightimes'],
                ['I\'m Gonna Be There', 'imgonnabethere'],
                ['When Will I Begin', 'whenwillibegin'],
                ['Walk Through This Life', 'walkthroughthislife'],
                ['Strengthen What Remains', 'strengthenwhatremains'],
                ['Still Your Mind', 'stillyourmind'],
                ['Hard Case', 'hardcase'],
                ['Shame', 'shame'],
                ['All the World', 'alltheworld'],
                ['They Don\'t Shine', 'theydontshine'],
                ['The Ending', 'theending'],
            ],
            'I Am the Moon' => [
                ['Hear My Dear', 'hearmydear'],
                ['Fall In', 'fallin'],
                ['I Am The Moon', 'iamthemoon'],
                ['Circles \'Round The Sun', 'circlesroundthesun'],
                ['Pasaquan', 'pasaquan'],
                ['Playing With My Emotions', 'playingwithmyemotions'],
                ['Ain\'t That Something', 'aintthatsomething'],
                ['All The Love', 'allthelove'],
                ['So Long Savior', 'solongsavior'],
                ['Rainy Day', 'rainyday'],
                ['La Di Da', 'ladida'],
                ['Hold That Line', 'holdthatline'],
                ['Somehow', 'somehow'],
                ['None Above', 'noneabove'],
                ['Yes We Will', 'yeswewill'],
                ['Gravity', 'gravity'],
                ['Emmaline', 'emmaline'],
                ['Take Me As I Am', 'takemeasiam'],
                ['Last Night In The Rain', 'lastnightintherain'],
                ['Soul Sweet Song', 'soulsweetsong'],
                ['D\'Gary', 'dgary'],
                ['Where Are My Friends?', 'wherearemyfriends'],
                ['I Can Feel You Smiling', 'icanfeelyousmiling'],
                ['Another Day', 'anotherday'],
            ],
        ],
        'Cabinet' => [
            'Cabinet' => [
                ['Elizabeth', 'elizabeth'],
                ['Shifty Shaft', 'shiftyshaft'],
                ['Caroline', 'caroline'],
                ['The Tower', 'thetower'],
                ['Dirt', 'dirt'],
                ['The Dove', 'thedove'],
                ['Coalminers', 'coalminers'],
                ['The One', 'theone'],
                ['Treesap', 'treesap'],
                ['The Old Farmer\'s Mill', 'theoldfarmersmill'],
                ['Carry On', 'carryon'],
            ],
            'Leap' => [
                ['Doors', 'doors'],
                ['Heavy Rain', 'heavyrain'],
                ['Two Timer', 'twotimer'],
                ['Eleanor', 'eleanor'],
                ['Susquehanna Breakdown', 'susquehannabreakdown'],
                ['Hit It On The Head', 'hititonthehead'],
                ['Wine And Shine', 'wineandshine'],
                ['Carry Me In A Bucket', 'carrymeinabucket'],
                ['Oxygen', 'oxygen'],
                ['Diamond Joe', 'diamondjoe'],
                ['Gather All Ye', 'gatherallye'],
            ],
            'Celebration' => [
                ['Old Time Songs', 'oldtimesongs'],
                ['Pennsylvania', 'pennsylvania'],
                ['Red River Valley', 'redrivervalley'],
                ['Celebration', 'celebration'],
                ['Pine Billy', 'pinebilly'],
                ['Home Now', 'homenow'],
            ],
        ],
        'Dogs in a Pile' => [
            'Not Your Average Beagle' => [
                ['Can\'t Wait for Tonight', 'cantwaitfortonight'],
                ['Thomas Duncan, Pt. 2', 'thomasduncanpt2'],
                ['Look Johnny', 'lookjohnny'],
                ['Renaissance Man', 'renaissanceman'],
                ['Snow Day', 'snowday'],
                ['Inchworm', 'inchworm'],
                ['Bubble', 'bubble'],
                ['Rinky Dink Rag', 'rinkydinkrag'],
                ['Bugle On the Shelf', 'bugleontheshelf'],
            ],
            'Bloom' => [
                ['Today', 'today'],
                ['Bent Strange', 'bentstrange'],
                ['All the Same', 'allthesame'],
                ['Stranger', 'stranger'],
                ['Fenway', 'fenway'],
                ['Hesitate', 'hesitate'],
                ['Trunk Rum', 'trunkrum'],
                ['Rum and Roses', 'rumandroses'],
                ['Say Something', 'saysomething'],
            ],
        ],
        'Phil Lesh and Friends' => [
            'There and Back Again' => [
                ['Celebration', 'celebration'],
                ['Night of a Thousand Stars', 'nightofathousandstars'],
                ['The Real Thing', 'therealthing'],
                ['Again and Again', 'againandagain'],
                ['No More Do I', 'nomoredoi'],
                ['Patchwork Quilt', 'patchworkquilt'],
                ['Liberty', 'liberty'],
                ['Midnight Train', 'midnighttrain'],
                ['Leave Me Out of This', 'leavemeoutofthis'],
                ['Welcome to the Underground', 'welcometotheunderground'],
                ['Rock-n-Roll Blues', 'rocknrollblues'],
                ['Passenger', 'passenger'],
                ['St. Stephen (4/4/02, Denver, Colorado, with Derek Trucks)', 'ststephen'],
                ['Dark Star (12/31/01, Oakland, California, with Derek Trucks)', 'darkstar'],
                ['The Eleven (3/30/02, San Francisco, California)', 'theeleven'],
            ],
        ],
        'Ratdog' => [
            'Evening Moods' => [
                ['Bury Me Standing', 'burymestanding'],
                ['Lucky Enough', 'luckyenough'],
                ['Odessa', 'odessa'],
                ['Ashes and Glass', 'ashesandglass'],
                ['Welcome to the World', 'welcometotheworld'],
                ['Two Djinn', 'twodjinn'],
                ['Corrina', 'corrina'],
                ['October Queen', 'octoberqueen'],
                ['The Deep End', 'thedeepend'],
                ['Even So', 'evenso'],
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
                'AddTracksGroup5 completed. Added %d tracks for %d artists.',
                $totalTracks,
                count(self::TRACKS)
            ));
        } catch (\Exception $e) {
            $this->logger->error('Failed to add tracks (Group 5): ' . $e->getMessage());
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
