# Artist/Album/Tracks Implementation Plan

## Overview

Add 30 new artists with their studio albums and tracks to the 8PM Music Platform. Artists, albums, and tracks are stored as Magento categories in a hierarchical structure.

## Current Status (Updated 2026-01-27)

### Completed
- Created data patch file: `src/app/code/ArchiveDotOrg/Core/Setup/Patch/Data/AddAdditionalArtists.php`
- Added 30 artists with ~200 albums (no tracks yet)
- Researched track listings for ~60 albums (see below)

### Remaining
- Search for track listings for remaining ~140 albums
- Update the data patch to include tracks as subcategories of albums
- Modify apply() method to create track categories

## Data Structure

The patch uses this format:
```php
private const CATEGORY_STRUCTURE = [
    'Artist Name' => [
        'url_key' => 'artisturlkey',
        'albums' => [
            [
                'Album Name',
                'albumurlkey',
                [  // tracks array
                    ['Track 1 Name', 'track1urlkey'],
                    ['Track 2 Name', 'track2urlkey'],
                    // ...
                ]
            ],
        ],
    ],
];
```

---

## Track Data Gathered

### Billy Strings

**Turmoil & Tinfoil (2017)**
1. On the Line
2. Meet Me at the Creek
3. All of Tomorrow
4. While I'm Waiting Here
5. Living Like an Animal
6. Turmoil & Tinfoil
7. Salty Sheep
8. Spinning
9. Dealing Despair
10. Pyramid Country
11. Doin' Things Right
12. These Memories of You

**Home (2019)**
1. Taking Water
2. Must Be Seven
3. Running
4. Away from the Mire
5. Home
6. Watch it Fall
7. Long Forgotten Dream
8. Highway Hypnosis
9. Enough to Leave
10. Hollow Heart
11. Love Like Me
12. Everything's the Same
13. Guitar Peace
14. Freedom

**Renewal (2021)**
1. Know It All
2. Secrets
3. Love And Regret
4. Heartbeat Of America
5. In The Morning Light
6. This Old World
7. Show Me The Door
8. Hellbender
9. Red Daisy
10. The Fire On My Tongue
11. Nothing's Working
12. Hide And Seek
13. Ice Bridges
14. Fire Line
15. Running The Route
16. Leaders

**Me/And/Dad (2022)**
1. Long Journey Home
2. Life to Go
3. Way Downtown
4. Little Blossom
5. Peartree
6. Stone Walls and Steel Bars
7. Little White Church
8. Dig a Little Deeper (In the Well)
9. Wandering Boy
10. I Haven't Seen Mary in Years
11. Frosty Morn
12. Catfish John
13. Blue Ridge Cabin Home
14. I Heard My Mother Weeping

**Highway Prayers (2024)**
1. Leaning on a Travelin' Song
2. In the Clear
3. Escanaba
4. Gild the Lily
5. Seven Weeks In County
6. Stratosphere Blues / I Believe in You
7. Cabin Song
8. Don't Be Calling Me (at 4AM)
9. Malfunction Junction
10. Catch and Release
11. Be Your Man
12. Gone a Long Time
13. It Ain't Before
14. My Alice
15. Seney Stretch
16. MORBUD4ME
17. Leadfoot
18. Happy Hollow
19. The Beginning of the End
20. Richard Petty

---

### Goose

**Moon Cabin (2016)**
1. Turned Clouds
2. Into The Myst
3. Arcadia
4. Lead The Way
5. Interlude I
6. Indian River
7. Interlude II
8. Jive I
9. Rosewood Heart
10. Interlude III
11. Jive II

**Shenanigans Nite Club (2021)**
1. So Ready
2. (s∆tellite)
3. Madhuvan
4. SOS
5. (dawn)
6. Flodown
7. Spirit Of The Dark Horse
8. (7hunder)
9. The Labyrinth

**Dripfield (2022)**
1. Borne
2. Hungersite
3. Dripfield
4. Slow Ready
5. The Whales
6. Arrow
7. Hot Tea
8. Moonrise
9. Honeybee
10. 726

**Everything Must Go (2025)**
1. Everything Must Go
2. Give It Time
3. Dustin Hoffman
4. Your Direction
5. Thatch
6. Lead Up
7. Animal
8. Red Bird
9. Atlas Dogs
10. California Magic
11. Feel It Now
12. Iguana Song
13. Silver Rising
14. How It Ends

**Chain Yer Dragon (2025)**
1. Hot Love & The Lazy Poet
2. Madalena
3. Royal
4. Turbulence & The Night Rays
5. Echoes of a Rose
6. Mr. Action
7. .....
8. Dr. Darkness
9. Empress of Organos
10. Jed Stone
11. Rockdale
12. Factory Fiction

---

### Grateful Dead

**The Grateful Dead (1967)**
1. The Golden Road (To Unlimited Devotion)
2. Cold Rain and Snow
3. Good Morning Little School Girl
4. Beat It On Down the Line
5. Sitting on Top of the World
6. Cream Puff War
7. Morning Dew
8. New, New Minglewood Blues
9. Viola Lee Blues

**Anthem of the Sun (1968)**
1. That's It for the Other One
2. New Potato Caboose
3. Born Cross-Eyed
4. Alligator
5. Caution (Do Not Stop on Tracks)

**Aoxomoxoa (1969)**
1. St. Stephen
2. Dupree's Diamond Blues
3. Rosemary
4. Doin' That Rag
5. Mountains of the Moon
6. China Cat Sunflower
7. What's Become of the Baby
8. Cosmic Charlie

**Workingman's Dead (1970)**
1. Uncle John's Band
2. High Time
3. Dire Wolf
4. New Speedway Boogie
5. Cumberland Blues
6. Black Peter
7. Easy Wind
8. Casey Jones

**American Beauty (1970)**
1. Box of Rain
2. Friend of the Devil
3. Sugar Magnolia
4. Operator
5. Candyman
6. Ripple
7. Brokedown Palace
8. Till the Morning Comes
9. Attics of My Life
10. Truckin'

**Wake of the Flood (1973)**
1. Mississippi Half-Step Uptown Toodeloo
2. Let Me Sing Your Blues Away
3. Row Jimmy
4. Stella Blue
5. Here Comes Sunshine
6. Eyes of the World
7. Weather Report Suite

**From the Mars Hotel (1974)**
1. U.S. Blues
2. China Doll
3. Unbroken Chain
4. Loose Lucy
5. Scarlet Begonias
6. Pride of Cucamonga
7. Money Money
8. Ship of Fools

**Blues for Allah (1975)**
1. Help on the Way
2. Slipknot!
3. Franklin's Tower
4. King Solomon's Marbles
5. The Music Never Stopped
6. Crazy Fingers
7. Sage & Spirit
8. Blues for Allah
9. Sand Castles & Glass Camels
10. Unusual Occurrences in the Desert

**Terrapin Station (1977)**
1. Estimated Prophet
2. Dancin' in the Streets
3. Passenger
4. Sunrise
5. Samson and Delilah
6. Terrapin Station

**Shakedown Street (1978)**
1. Good Lovin'
2. France
3. Shakedown Street
4. Serengetti
5. Fire on the Mountain
6. I Need a Miracle
7. From the Heart of Me
8. Stagger Lee
9. All New Minglewood Blues
10. If I Had the World to Give

**Go to Heaven (1980)**
1. Alabama Getaway
2. Far from Me
3. Althea
4. Feel Like a Stranger
5. Lost Sailor
6. Saint of Circumstance
7. Antwerp's Placebo (The Plumber)
8. Easy to Love You
9. Don't Ease Me In

**In the Dark (1987)**
1. Touch of Grey
2. Hell in a Bucket
3. When Push Comes to Shove
4. West L.A. Fadeaway
5. Tons of Steel
6. Throwing Stones
7. Black Muddy River

**Built to Last (1989)**
1. Foolish Heart
2. Just a Little Light
3. Built to Last
4. Blow Away
5. Standing on the Moon
6. Victim or the Crime
7. We Can Run
8. Picasso Moon
9. I Will Take You Home

---

### Phish

**Junta (1989)**
1. Fee
2. You Enjoy Myself
3. Esther
4. Golgi Apparatus
5. Foam
6. Dinner and a Movie
7. The Divided Sky
8. David Bowie
9. Fluffhead
10. Fluff's Travels
11. Contact

**Lawn Boy (1990)**
1. The Squirming Coil
2. Reba
3. My Sweet One
4. Split Open and Melt
5. The Oh Kee Pah Ceremony
6. Bathtub Gin
7. Run Like an Antelope
8. Lawn Boy
9. Bouncing Around the Room

**A Picture of Nectar (1992)**
1. Llama
2. Eliza
3. Cavern
4. Poor Heart
5. Stash
6. Manteca
7. Guelah Papyrus
8. Magilla
9. The Landlady
10. Glide
11. Tweezer
12. The Mango Song
13. Chalk Dust Torture
14. Faht
15. Catapult
16. Tweezer Reprise

**Rift (1993)**
1. Rift
2. Fast Enough for You
3. Lengthwise
4. Maze
5. Sparkle
6. Horn
7. The Wedge
8. My Friend, My Friend
9. Weigh
10. All Things Reconsidered
11. Mound
12. It's Ice
13. The Horse
14. Silent in the Morning

**Hoist (1994)**
1. Julius
2. Down with Disease
3. If I Could
4. Riker's Mailbox
5. Axilla (Part II)
6. Lifeboy
7. Sample in a Jar
8. Wolfman's Brother
9. Scent of a Mule
10. Dog Faced Boy
11. Demand

**Billy Breathes (1996)**
1. Free
2. Character Zero
3. Waste
4. Taste
5. Cars Trucks Buses
6. Talk
7. Theme from the Bottom
8. Train Song
9. Bliss
10. Billy Breathes
11. Swept Away
12. Steep
13. Prince Caspian

**Story of the Ghost (1998)**
1. Ghost
2. Birds of a Feather
3. Meat
4. Guyute
5. Fikus
6. Shafty
7. Limb by Limb
8. Frankie Says
9. Brian and Robert
10. Water in the Sky
11. Roggae
12. Wading in the Velvet Sea
13. The Moma Dance
14. End of Session

**The Siket Disc (1999)**
1. What's the Use?
2. My Left Toe
3. The Happy Whip and Dung Song
4. Quadrophonic Toppling
5. Insects
6. Title Track
7. Albert
8. The Name is Slick
9. Farmhouse Recording Session

**Farmhouse (2000)**
1. Farmhouse
2. Twist
3. Bug
4. Back on the Train
5. Heavy Things
6. Gotta Jibboo
7. Dirt
8. Piper
9. Sleep
10. The Inlaw Josie Wales
11. Sand
12. First Tube

**Round Room (2002)**
1. Pebbles and Marbles
2. Anything But Me
3. Round Room
4. Mexican Cousin
5. Friday
6. Seven Below
7. Mock Song
8. 46 Days
9. All of These Dreams
10. Walls of the Cave
11. Thunderhead
12. Waves

**Undermind (2004)**
1. Scents and Subtle Sounds (Intro)
2. Undermind
3. The Connection
4. A Song I Heard the Ocean Sing
5. Army of One
6. Crowd Control
7. Maggie's Revenge
8. Nothing
9. Two Versions of Me
10. Access Me
11. Scents and Subtle Sounds
12. Tomorrow's Song
13. Secret Smile
14. Grind

**Joy (2009)**
1. Backwards Down the Number Line
2. Stealing Time from the Faulty Plan
3. Joy
4. Sugar Shack
5. Ocelot
6. Kill Devil Falls
7. Light
8. I Been Around
9. Time Turns Elastic
10. Twenty Years Later

**Fuego (2014)**
1. Fuego
2. The Line
3. Devotion to a Dream
4. Halfway to the Moon
5. Winterqueen
6. Sing Monica
7. 555
8. Waiting All Night
9. Wombat
10. Wingsuit

**Big Boat (2016)**
1. Friends
2. Breath and Burning
3. Home
4. Blaze On
5. Tide Turns
6. Things People Do
7. Waking Up Dead
8. Running Out of Time
9. No Men in No Man's Land
10. Miss You
11. I Always Wanted It This Way
12. More
13. Petrichor

**Sigma Oasis (2020)**
1. Sigma Oasis
2. Leaves
3. Everything's Right
4. Mercury
5. Shade
6. Evening Song
7. Steam
8. A Life Beyond the Dream
9. Thread

**Sci-Fi Soldier / Get More Down (2022)**
1. Knuckle Bone Broth Avenue
2. Get More Down
3. Egg in a Hole
4. Thanksgiving
5. Clear Your Mind
6. The 9th Cube
7. The Inner Reaches of Outer
8. Don't Doubt Me
9. The Unwinding
10. Something Living Here
11. The Howling
12. I Am in Miami

---

### Smashing Pumpkins

**Gish (1991)**
1. I Am One
2. Siva
3. Rhinoceros
4. Bury Me
5. Crush
6. Suffer
7. Snail
8. Tristessa
9. Window Paine
10. Daydream

**Siamese Dream (1993)**
1. Cherub Rock
2. Quiet
3. Today
4. Hummer
5. Rocket
6. Disarm
7. Soma
8. Geek U.S.A.
9. Mayonaise
10. Spaceboy
11. Silverfuck
12. Sweet Sweet
13. Luna

**Mellon Collie and the Infinite Sadness (1995)** - NEEDS FULL TRACKLIST (28 tracks)

**Adore (1998)**
1. To Sheila
2. Ava Adore
3. Perfect
4. Daphne Descends
5. Once Upon a Time
6. Tear
7. Crestfallen
8. Appels + Oranjes
9. Pug
10. The Tale of Dusty and Pistol Pete
11. Annie-Dog
12. Shame
13. Behold! The Night Mare
14. For Martha
15. Blank Page

**Machina/The Machines of God (2000)** - NEEDS FULL TRACKLIST (15 tracks)

**Machina II/The Friends & Enemies of Modern Music (2000)** - NEEDS FULL TRACKLIST (25 tracks)

**Zeitgeist (2007)** - NEEDS FULL TRACKLIST (12 tracks)

**Oceania (2012)**
1. Quasar
2. Panopticon
3. The Celestials
4. Violet Rays
5. My Love Is Winter
6. One Diamond, One Heart
7. Pinwheels
8. Oceania
9. Pale Horse
10. The Chimera
11. Glissandra
12. Inkless
13. Wildflower

**Monuments to an Elegy (2014)** - NEEDS FULL TRACKLIST (9 tracks)

**Shiny and Oh So Bright, Vol. 1 (2018)**
1. Knights of Malta
2. Silvery Sometimes (Ghosts)
3. Travels
4. Solara
5. Alienation
6. Marchin' On
7. With Sympathy
8. Seek and You Shall Destroy

**Cyr (2020)**
1. The Colour of Love
2. Confessions of a Dopamine Addict
3. Cyr
4. Dulcet in E
5. Wrath
6. Ramona
7. Anno Satana
8. Birch Grove
9. Wyttch
10. Starrcraft
11. Purple Blood
12. Save Your Tears
13. Telegenix
14. Black Forest, Black Hills
15. Adrennalynne
16. Haunted
17. The Hidden Sun
18. Schaudenfreud
19. Tyger, Tyger
20. Minerva

**Atum: A Rock Opera in Three Acts (2023)** - NEEDS FULL TRACKLIST (33 tracks)

**Aghori Mhori Mei (2024)**
1. Edin
2. Pentagrams
3. Sighommi
4. Pentecost
5. War Dreams of Itself
6. Who Goes There
7. 999
8. Goeth the Fall
9. Sicarus
10. Murnau

---

### Widespread Panic

**Space Wrangler (1988)**
1. Chilly Water
2. Travelin' Light
3. Space Wrangler
4. Coconut
5. The Take Out
6. Porch Song
7. Stop-Go
8. Driving Song
9. Holden Oversoul
10. Contentment Blues
11. Gomero Blanco
12. Me and The Devil Blues / Heaven

**Widespread Panic (1991)**
1. Walkin' (For Your Love)
2. Pigeons
3. Mercy
4. Makes Sense To Me
5. C. Brown
6. Love Tractor
7. Weight of the World
8. I'm Not Alone
9. Barstools And Dreamers
10. Proving Ground
11. The Last Straw
12. Send Your Mind

**Everyday (1993)**
1. Pleas
2. Hatfield
3. Wondering
4. Papa's Home
5. Diner
6. Pilgrims
7. Pickin' Up The Pieces
8. Postcard
9. Everyday
10. Henry Parsons Died
11. Fishwater

**Ain't Life Grand (1994)** - NEEDS FULL TRACKLIST (11 tracks)

**Bombs & Butterflies (1997)** - NEEDS FULL TRACKLIST (10 tracks)

**'Til the Medicine Takes (1999)**
1. Surprise Valley
2. Blue Indian
3. Party At Your Mama's House
4. All Time Low
5. Climb to Safety
6. Nobody's Loss
7. Dyin' Man
8. The Waker
9. One Arm Steve
10. You'll Be Fine
11. Bear's Gone Fishin'
12. Christmas Katie

**Don't Tell the Band (2001)** - NEEDS FULL TRACKLIST (12 tracks)

**Ball (2003)** - NEEDS FULL TRACKLIST (13 tracks)

**Earth to America (2006)**
1. Second Skin
2. Goodpeople
3. From The Cradle
4. Solid Rock
5. Time Zones
6. When the Clowns Come Home
7. Ribs and Whiskey
8. Crazy
9. You Should Be Glad
10. May Your Glass Be Filled

**Free Somehow (2008)**
1. Boom Boom Boom
2. Walk On the Flood
3. Angels On High
4. Three Candles
5. Tickle the Truth
6. Free Somehow
7. Flicker
8. Dark Day Program
9. Her Dance Needs No Body
10. Already Fried
11. Up All Night

**Dirty Side Down (2010)**
1. Saint Ex
2. North
3. Dirty Side Down
4. This Cruel Thing
5. Visiting Day
6. Clinic Cynic
7. St. Louis
8. Shut Up and Drive
9. True to My Nature
10. When You Coming Home
11. Jaded Tourist
12. Cotton Was King

**Street Dogs (2015)**
1. Sell Sell
2. Steven's Cat
3. Cease Fire
4. Jamais Vu (The World Has Changed)
5. Angels Don't Sing The Blues
6. Honky Red
7. The Poorhouse Of Positive Thinking
8. Welcome To My World
9. Tail Dragger
10. Street Dogs For Breakfast

**Snake Oil King (2024)**
1. Little By Little
2. We Walk Each Other Home
3. Tackle Box Hero
4. Life as a Tree
5. Cosmic Confidante
6. Small Town

**Hailbound Queen (2024)**
1. King Baby
2. Blue Carousel
3. Keep Me In Your Heart
4. Trashy
5. Halloween Face

---

### moe.

**Fatboy (1992)** - NEEDS FULL TRACKLIST (8 tracks)

**Headseed (1994)** - NEEDS FULL TRACKLIST (10 tracks)

**No Doy (1996)**
1. She Sends Me
2. 32 Things
3. Saint Augustine
4. Bring You Down
5. Rebubula
6. Spine of a Dog
7. Moth
8. Buster
9. Four

**Tin Cans and Car Tires (1998)** - NEEDS RESEARCH
**Dither (2001)** - NEEDS RESEARCH
**Season's Greetings from Moe (2003)** - NEEDS RESEARCH
**Wormwood (2003)** - NEEDS RESEARCH
**The Conch (2006)** - NEEDS RESEARCH
**Sticks & Stones (2008)** - NEEDS RESEARCH
**What Happened To The La Las (2012)** - NEEDS RESEARCH
**No Guts No Glory (2014)** - NEEDS RESEARCH
**This Is Not, We Are (2020)** - NEEDS RESEARCH
**Circle of Giants (2025)** - NEEDS RESEARCH

---

### John Mayer

**Room for Squares (2001)**
1. No Such Thing
2. Why Georgia
3. My Stupid Mouth
4. Your Body Is a Wonderland
5. Neon
6. City Love
7. 83
8. 3x5
9. Love Song for No One
10. Back to You
11. Great Indoors
12. Not Myself
13. St. Patrick's Day

**Heavier Things (2003)**
1. Clarity
2. Bigger Than My Body
3. Something's Missing
4. New Deep
5. Come Back to Bed
6. Home Life
7. Split Screen Sadness
8. Daughters
9. Only Heart
10. Wheel

**Continuum (2006)**
1. Waiting on the World to Change
2. I Don't Trust Myself (With Loving You)
3. Belief
4. Gravity
5. The Heart of Life
6. Vultures
7. Stop This Train
8. Slow Dancing in a Burning Room
9. Bold as Love
10. Dreaming with a Broken Heart
11. In Repair
12. I'm Gonna Find Another You

**Battle Studies (2009)**
1. Heartbreak Warfare
2. All We Ever Do Is Say Goodbye
3. Half of My Heart
4. Who Says
5. Perfectly Lonely
6. Assassin
7. Crossroads
8. War of My Life
9. Edge of Desire
10. Do You Know Me
11. Friends, Lovers or Nothing

**Born and Raised (2012)** - NEEDS FULL TRACKLIST (12 tracks)

**Paradise Valley (2013)** - NEEDS RESEARCH

**The Search for Everything (2017)** - NEEDS RESEARCH

**Sob Rock (2021)**
1. Last Train Home
2. Shouldn't Matter but It Does
3. New Light
4. Why You No Love Me
5. Wild Blue
6. Shot in the Dark
7. I Guess I Just Feel Like
8. Til the Right One Comes
9. Carry Me Away
10. All I Want Is to Be With You

---

### Umphrey's McGee

**Anchor Drops (2004)**
1. Plunger
2. Anchor Drops
3. In The Kitchen
4. Bullhead City
5. Miss Tinkle's Overture
6. Uncommon
7. Jajunk Pt. I
8. 13 Days
9. Jajunk Pt. II
10. Walletsworth
11. Robot World
12. Mulche's Odyssey
13. Wife Soup
14. The Pequod

**Other albums** - NEEDS RESEARCH (11 albums)

---

### My Morning Jacket

**It Still Moves (2003)**
1. Mahgeetah
2. Dancefloors
3. Golden
4. Master Plan
5. One Big Holiday
6. I Will Sing You Songs
7. Easy Morning Rebel
8. Run Thru
9. Rollin' Back
10. Just One Thing
11. Steam Engine
12. One In The Same

**Z (2005)**
1. Wordless Chorus
2. It Beats For You
3. Gideon
4. What A Wonderful Man
5. Off The Record
6. Into The Woods
7. Anytime
8. Lay Low
9. Knot Comes Loose
10. Dondante

**Other albums** - NEEDS RESEARCH (8 albums)

---

### Tedeschi Trucks Band

**Revelator (2011)**
1. Come See About Me
2. Don't Let Me Slide
3. Midnight In Harlem
4. Bound For Glory
5. Simple Things
6. Until You Remember
7. Ball And Chain
8. These Walls
9. Learn How To Love
10. Shrimp And Grits (Interlude)
11. Love Has Something Else To Say
12. Shelter

**Other albums** - NEEDS RESEARCH (3 albums)

---

### Lettuce

**Fly! (2012)**
1. Fly
2. Lettsanity
3. Ziggowatt
4. Madison Square
5. Bowler
6. Jack Flask
7. Do It Like You Do
8. Play
9. Let It GOGO
10. Slippin' Into Darkness
11. The Crusher
12. Ghost of Jupiter

**Other albums** - NEEDS RESEARCH (8 albums)

---

### Warren Zevon

**Excitable Boy (1978)**
1. Johnny Strikes Up The Band
2. Roland The Headless Thompson Gunner
3. Excitable Boy
4. Werewolves of London
5. Accidentally Like A Martyr
6. Nighttime In The Switching Yard
7. Veracruz
8. Tenderness On The Block
9. Lawyers, Guns and Money

**Other albums** - NEEDS RESEARCH (11 albums)

---

### Ween

**The Mollusk (1997)** - PARTIAL
1. Buckingham Green
2. I'll Be Your Jonny on the Spot
3. The Mollusk
4. Mutilated Lips
5. I'm Dancing in the Show Tonight
6. Ocean Man
7. The Golden Eel
8. The Blarney Stone
9. Cold Blows the Wind
10. It's Gonna Be (Alright)
11. Polka Dot Tail
12. Waving My Dick in the Wind
13. She Wanted to Leave
14. Pink Eye (On My Leg)

**Other albums** - NEEDS RESEARCH (8 albums)

---

## Albums Still Needing Track Research

### Complete List by Artist

**King Gizzard & The Lizard Wizard** - 27 albums (ALL need research)
**Keller Williams** - 21 albums (ALL need research)
**Ween** - 8 remaining albums
**Warren Zevon** - 11 remaining albums
**moe.** - 10 remaining albums
**Guster** - 9 albums (ALL need research)
**Yonder Mountain String Band** - 9 albums (ALL need research)
**My Morning Jacket** - 8 remaining albums
**Lettuce** - 8 remaining albums
**John Mayer** - 2 remaining albums
**Matisyahu** - 8 albums (ALL need research)
**Leftover Salmon** - 8 albums (ALL need research)
**Rusted Root** - 7 albums (ALL need research)
**God Street Wine** - 6 albums (ALL need research)
**Twiddle** - 5 albums (ALL need research)
**Smashing Pumpkins** - 5 partial albums
**Widespread Panic** - 4 remaining albums
**Tedeschi Trucks Band** - 3 remaining albums
**Cabinet** - 3 albums (ALL need research)
**Dogs in a Pile** - 2 albums (ALL need research)
**Umphrey's McGee** - 11 remaining albums
**Phil Lesh and Friends** - 1 album (partial)
**Ratdog** - 1 album (needs research)

**Total Albums Needing Research:** ~140

---

## URL Key Generation Rules

- Lowercase everything
- Remove spaces
- Remove special characters (apostrophes, ampersands, etc.)
- Keep alphanumeric only
- Examples:
  - "Uncle John's Band" → "unclejohnsband"
  - "You Enjoy Myself" → "youenjoymyself"
  - "Box of Rain" → "boxofrain"

---

## File Locations

- **Data patch:** `/Users/chris.majorossy/Projects/docker-desktop/8pm/src/app/code/ArchiveDotOrg/Core/Setup/Patch/Data/AddAdditionalArtists.php`
- **Original patch (reference):** `/Users/chris.majorossy/Projects/docker-desktop/8pm/src/app/code/ArchiveDotOrg/Core/Setup/Patch/Data/CreateCategoryStructure.php`
- **This plan:** `/Users/chris.majorossy/Projects/docker-desktop/8pm/ARTIST_ALBUM_TRACKS_PLAN.md`

---

## Next Steps

1. **Continue searching** for track listings for remaining ~140 albums
2. **Update the data patch file** with track arrays
3. **Modify the apply() method** to create track subcategories under albums with `is_song=1`
4. Run `bin/magento setup:upgrade` to create all categories

---

## To Apply Changes

After updating the patch file with all tracks:

```bash
bin/magento setup:upgrade
```

This will create all artists, albums, and tracks as Magento categories.
