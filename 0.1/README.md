the unofficial

#RealmEye API

A quick and easy portal to getting all the character information you need.
(v0.1)

##How to use

Currently, the only method available is to get a player's personal and character info, as seen on RealmEye in JSON (or JSONP) format.  

* * *

##Request

<table>
	<tr>
		<th>Name</th>
		<th>Method</th>
		<th>Description</th>
	</tr>
	<tr>
		<td>http://webhost.ischool.uw.edu/~joatwood/realmeye_api/0.1/</td>
		<td>GET</td>
		<td>Get player and character data.</td>
	</tr>
</table>

###Parameters

<table>
	<tr>
		<th>Name</th>
		<th>Data Type</th>
		<th>Required / Optional</th>
		<th>Description</th>
	</tr>
	<tr>
		<td>player</td>
		<td>string</td>
		<td>optional</td>
		<td>Player name. (case insensitive) If not included, <code>id</code> is required</td>
	</tr>
	<tr>
		<td>id</td>
		<td>string</td>
		<td>optional</td>
		<td>11-character id <a href="https://www.realmeye.com/recently-seen-unnamed-players">as it appears in an unnamed player's RealmEye URL</a>. If not included, <code>player</code> is requred</td>
	</tr>
	<tr>
		<td>callback</td>
		<td>string</td>
		<td>optional</td>
		<td>JavaScript callback function name. If passed, response will be served as JSONP</td>
	</tr>
</table>

##Response values

<table>
	<tr>
		<th>Name</th>
		<th>Type</th>
		<th>Definition</th>
	</tr>
	<tr>
		<td>player</td>
		<td>string</td>
		<td>Player name, cased as seen on their profile</td>
	</tr>
	<tr>
		<td>chars</td>
		<td>int / string</td>
		<td>Count of characters seen on RealmEye. If characters are hidden, returns "N/A"</td>
	</tr>
	<tr>
		<td>fame</td>
		<td>int</td>
		<td>Total character fame</td>
	</tr>
	<tr>
		<td>fame_rank</td>
		<td>int</td>
		<td>Fame placement</td>
	</tr>
	<tr>
		<td>exp</td>
		<td>int</td>
		<td>Total character experience</td>
	</tr>
	<tr>
		<td>exp_rank</td>
		<td>int</td>
		<td>Experience placement</td>
	</tr>
	<tr>
		<td>rank</td>
		<td>int</td>
		<td>Class quests completed</td>
	</tr>
	<tr>
		<td>account_fame</td>
		<td>int</td>
		<td>Account fame. (aka 'dead' fame)</td>
	</tr>
	<tr>
		<td>account_fame_rank</td>
		<td>int</td>
		<td>Account fame placement</td>
	</tr>
	<tr>
		<td>guild</td>
		<td>string</td>
		<td>Guild name. Not present for guildless players</td>
	</tr>
	<tr>
		<td>guild_rank</td>
		<td>string</td>
		<td>Guild position title. (Initiate, Member, Officer, Leader, or Founder) Not present for guildless players</td>
	</tr>
	<tr>
		<td>created</td>
		<td>string</td>
		<td>Approximation of account age</td>
	</tr>
	<tr>
		<td>last_seen</td>
		<td>string</td>
		<td>"{datetime} at {server} as {class}". If last-seen time/location is hidden, returns "hidden"</td>
	</tr>
	<tr>
		<td>characters</td>
		<td>array</td>
		<td>Array of displayed characters</td>
	</tr>
</table>

###character

<table>
	<tr>
		<th>Name</th>
		<th>Type</th>
		<th>Definition</th>
	</tr>
	<tr>
		<td>pet</td>
		<td>string</td>
		<td>Pet type (not player-assigned pet name)</td>
	</tr>
	<tr>
		<td>character_dyes</td>
		<td>dict</td>
		<td>List of character dyes as strings. <code>clothing_dye</code> is the name of the large cloth/dye, and <code>accessory_dye</code> is the name of the small cloth/dye. Cloth/dye name is empty if un-dyed</td>
	</tr>
	<tr>
		<td>class</td>
		<td>string</td>
		<td>Class name</td>
	</tr>
	<tr>
		<td>level</td>
		<td>int</td>
		<td>Character level</td>
	</tr>
	<tr>
		<td>cqc</td>
		<td>int</td>
		<td>Class quests completed on character's class</td>
	</tr>
	<tr>
		<td>fame</td>
		<td>int</td>
		<td>Fame on character</td>
	</tr>
	<tr>
		<td>exp</td>
		<td>int</td>
		<td>Experience on character</td>
	</tr>
	<tr>
		<td>place</td>
		<td>int</td>
		<td>Character rank placement</td>
	</tr>
	<tr>
		<td>equips</td>
		<td>dict</td>
		<td>List of equipments as strings. Keys are <code>weapon</code>, <code>ability</code>, <code>armor</code>, and <code>ring</code>. Empty slots' values are "Empty slot"</td>
	</tr>
	<tr>
		<td>backpack</td>
		<td>boolean (string)</td>
		<td>"true" if character has a backpack, "false" otherwise</td>
	</tr>
	<tr>
		<td>stats_maxed</td>
		<td>int</td>
		<td>Number (out of 8) of stats maxed on character</td>
	</tr>
	<tr>
		<td>last_seen</td>
		<td>datetime</td>
		<td>"{year}-{month}-{day} {hour}:{minute}:{second}". If last-seen time/location is hidden, returns ""</td>
	</tr>
	<tr>
		<td>last_server</td>
		<td>string</td>
		<td>Full name of last server seen in. (e.g. "USNorthWest") If last-seen time/location is hidden, returns ""</td>
	</tr>
</table>

###Sample requests

    http://webhost.ischool.uw.edu/~joatwood/realmeye_api/0.1/?player=joanofarc
    http://webhost.ischool.uw.edu/~joatwood/realmeye_api/0.1/?id=PdT6pPU7qBN&callback=processPlayer

###Sample response (for player=joanofarc)

    {
        "player"               : "JoanOfArc",
        "chars"                : 13,
        "fame"                 : 8300,
        "fame_rank"            : 497,
        "exp"                  : 13152470,
        "exp_rank"             : 513,
        "rank"                 : 62,
        "account_fame"         : 35662,
        "account_fame_rank"    : 264,
        "guild"                : "Night Owls",
        "guild_rank"           : "Officer",
        "created"              : "~1 year and 137 days ago",
        "last_seen"            : "2013-08-02 07:04:16 at USNorthWest as Rogue",
        "characters"           : [
            {
                "pet"                  : "Gummy Bear",
                "character_dyes"       : {
                    "clothing_dye"         : "Large Blue Lace Cloth",
                    "accessory_dye"        : "Small Sweater Cloth"
                },
                "class"                : "Rogue",
                "level"                : 20,
                "cqc"                  : 4,
                "fame"                 : 608,
                "exp"                  : 805974,
                "place"                : 589,
                "equips"               : {
                    "weapon"               : "Dirk of Cronus",
                    "ability"              : "Cloak of Ghostly Concealment",
                    "armor"                : "Spectral Cloth Armor",
                    "ring"                 : "Ring of the Pyramid"
                },
                "backpack"             : "true",
                "stats_maxed"          : 8,
                "last_seen"            : "2013-08-02 07:04:16",
                "last_server"          : "USNorthWest"
            },
            //... (all other characters)
        ]
    }