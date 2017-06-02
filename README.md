the unofficial

# RealmEye API

A quick and easy portal to getting all the character information you need.

## How to use

Currently, the only method available is to get a player's personal and character info, as seen on RealmEye in JSON (or JSONP) format.

## Setup

To run this API on your server, you will need to ensure you have the proper
software installed, and correctly set up your environment.

To run the API, you will need the following installed on your server:

* Latest Apache (known working on 2.4.20)
* Latest PHP (known working on 7.1.5)
* *recommended:* Git (known working with 2.7.4)

Once the above are installed and configured, create your desired endpoint
directory within your webroot and `git clone` this repository.

### Automated GitHub deployment

For ease of maintenance, a deployment script has been provided for
administrators. To make use of it, simply create a copy of the provided
`config.ini.sample` file, rename it to `config.ini`, and add a list of IPs
allowed to run the script (manually perform deploy steps) and add the secret
key used for the GitHub webhook.

For more information on setting up a webhook for automated deployment, read
GitHub's articles [on Webhooks](https://developer.github.com/webhooks/), and
[on Securing Webhooks](https://developer.github.com/webhooks/securing/).

* * *

## Requests

<table>
	<tr>
		<th>Name</th>
		<th>Method</th>
		<th>Description</th>
	</tr>
	<tr>
		<td>https://nightfirec.at/realmeye-api/</td>
		<td>GET</td>
		<td>Get player and character data.</td>
	</tr>
</table>

### Parameters

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
		<td>filter</td>
		<td>string</td>
		<td>optional</td>
		<td>
			Accepts a space-separated string of variable names, with an optional leading hyphen.
			When not prefaced with a hyphen, directs the API to return only values with keys appearing in the given list.
			When prefaced with a hyphen, directs the API to return all usual values except those matching the provided keys.
			<br /><br />
			Note: values nested within arrays will not be displayed unless their parent key is listed as well.
			(ex. <code>class</code> for characters will not be displayed unless <code>character</code> is also passed within the list)
		</td>
	</tr>
	<tr>
		<td>callback</td>
		<td>string</td>
		<td>optional</td>
		<td>JavaScript callback function name. If passed, response will be served as JSONP</td>
	</tr>
	<tr>
		<td>pretty</td>
		<td>N/A</td>
		<td>optional</td>
		<td>Empty parameter. If passed, output will be provided in a line-and-tab separated format</td>
	</tr>
</table>

## Response values

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
		<td>donator</td>
		<td>boolean</td>
		<td><code>true</code> if the player donated to Realmeye, <code>false</code> otherwise</td>
	</tr>
	<tr>
		<td>chars</td>
		<td>int</td>
		<td>Count of characters seen on RealmEye. If characters are hidden, this is <code>-1</code></td>
	</tr>
	<tr>
		<td>skins</td>
		<td>int</td>
		<td>Number of skins unlocked. If characters are hidden, this is <code>-1</code></td>
	</tr>
	<tr>
		<td>skins_rank</td>
		<td>int</td>
		<td>Rank of number of skins. If characters are hidden, this is <code>-1</code></td>
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
		<td>Guild name. This value is <code>""</code> for guildless players</td>
	</tr>
	<tr>
		<td>guild_rank</td>
		<td>string</td>
		<td>Guild position title. (Initiate, Member, Officer, Leader, or Founder) This value is <code>""</code> for guildless players</td>
	</tr>
	<tr>
		<td>created</td>
		<td>string</td>
		<td>Approximation of account age. May display <code>"hidden"</code> by player preference</td>
	</tr>
	<tr>
		<td>player_last_seen</td>
		<td>string</td>
		<td><code>"{datetime} at {server} as {class}"</code>. May display <code>"hidden"</code> by player preference</td>
	</tr>
	<tr>
		<td>desc1, desc2, desc3</td>
		<td>string</td>
		<td>Full strings of each description line (by numbered line) of the player. If the given line is empty, this value is <code>""</code></td>
	</tr>
	<tr>
		<td>characters</td>
		<td>array</td>
		<td>Array of displayed characters. If characters are hidden, this array will be empty and <strong>characters_hidden</strong> will be <code>true</code></td>
	</tr>
	<tr>
		<td>characters_hidden</td>
		<td>boolean</td>
		<td><code>true</code> if the player's characters are hidden, <code>false</code> otherwise</td>
	</tr>
</table>

### character

<table>
	<tr>
		<th>Name</th>
		<th>Type</th>
		<th>Definition</th>
	</tr>
	<tr>
		<td>data_pet_id</td>
		<td>int</td>
		<td>Item <code>id</code> of the given pet. <code>-1</code> if character has no pet</td>
	</tr>
	<tr>
		<td>pet</td>
		<td>string</td>
		<td>Pet type. (not player-assigned pet name) <code>""</code> if character has no pet</td>
	</tr>
	<tr>
		<td>character_dyes</td>
		<td>dict</td>
		<td>
			Dictionary of character dyes as strings and dye data as ints.<br />
			<code>clothing_dye</code> is the name of the large cloth/dye<br />
			<code>accessory_dye</code> is the name of the small cloth/dye<br /><br />
			<code>data_clothing_dye</code> is the numbered <code>id</code> of the <em>color</em>, not of the item. (for use in rendering character images, for example)<br />
			<code>data_accessory_dye</code> is the numbered <code>id</code> of the <em>color</em>, not of the item. (for use in rendering charcter images, for example)<br />
			Data values are <code>0</code> for undyed characters. Cloth/dye names are <code>""</code> if un-dyed
		</td>
	</tr>
	<tr>
		<td>class</td>
		<td>string</td>
		<td>Class name</td>
	</tr>
	<tr>
		<td>data_class_id</td>
		<td>int</td>
		<td><code>id</code> assigned to that character's class</td>
	</tr>
	<tr>
		<td>data_skin_id</td>
		<td>int</td>
		<td><code>id</code> assigned to that character's skin. 0 if character is using the class's default skin</td>
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
		<td>
			Dictionary of equipments as strings and item <code>id</code>s as ints.<br />
			Name keys are <strong>weapon</strong>, <strong>ability</strong>, <strong>armor</strong>, and <strong>ring</strong>. Data-variable keys are <strong>data_weapon_id</strong>, <strong>data_ability_id</strong>, <strong>data_armor_id</strong>, and <strong>data_ring_id</strong>.<br />
			Empty slots' values are <code>"Empty slot"</code> and <code>-1</code>, respectively.
		</td>
	</tr>
	<tr>
		<td>backpack</td>
		<td>boolean</td>
		<td><code>true</code> if character has a backpack, <code>false</code> otherwise</td>
	</tr>
	<tr>
		<td>stats_maxed</td>
		<td>int</td>
		<td>Number (out of 8) of stats maxed on character</td>
	</tr>
	<tr>
		<td>stats</td>
		<td>dict</td>
		<td>
			Dictionary of individual base stats (<strong>hp</strong>, <strong>mp</strong>, <strong>attack</strong>, <strong>defense</strong>, <strong>speed</strong>, <strong>vitality</strong>, <strong>wisdom</strong>, and <strong>dexterity</strong>) as ints.
		</td>
	</tr>
	<tr>
		<td>last_seen</td>
		<td>string</td>
		<td><code>"YYYY-MM-DD hh:mm:ss"</code>. If last-seen time/location is hidden, this value is <code>""</code></td>
	</tr>
	<tr>
		<td>last_server</td>
		<td>string</td>
		<td>Full name of last server seen in. (e.g. <code>"USNorthWest"</code>) If last-seen time/location is hidden, this value is <code>""</code></td>
	</tr>
</table>

### Sample requests

* `GET https://nightfirec.at/realmeye-api/?player=joanofarc`
* `GET https://nightfirec.at/realmeye-api/?id=PdT6pPU7qBN&callback=processPlayer`
* `GET https://nightfirec.at/realmeye-api/?player=joanofarc&filter=player+chars+fame`
* `GET https://nightfirec.at/realmeye-api/?player=joanofarc&filter=-characters+desc1+desc2+desc3`
* `GET https://nightfirec.at/realmeye-api/?player=joanofarc&pretty`

### Sample responses

For `player=joanofarc`:

```
{
    "account_fame"         : 35662,
    "account_fame_rank"    : 264,
    "characters"           : [
        {
            "backpack"             : true,
            "character_dyes"       : {
                "accessory_dye"        : "Small Sweater Cloth"
                "clothing_dye"         : "Large Blue Lace Cloth",
                "data_accessory_dye"   : 83886083,
                "data_clothing_dye"    : 150994946,
            },
            "class"                : "Rogue",
            "cqc"                  : 4,
            "data_class_id"        : 768,
            "data_pet_id"          : 32611,
            "data_skin_id"         : 913,
            "equips"               : {
                "ability"              : "Cloak of Ghostly Concealment",
                "armor"                : "Spectral Cloth Armor",
                "data_ability_id"      : 2855,
                "data_armor_id"        : 3112,
                "data_ring_id"         : 2978,
                "data_weapon_id"       : 3082,
                "ring"                 : "Ring of the Pyramid"
                "weapon"               : "Dirk of Cronus",
            },
            "exp"                  : 805974,
            "fame"                 : 608,
            "last_seen"            : "2013-08-02 07:04:16",
            "last_server"          : "USNorthWest"
            "level"                : 20,
            "pet"                  : "Gummy Bear",
            "place"                : 589,
            "stats"                : {
                "attack"               : 50,
                "defense"              : 25,
                "dexterity"            : 75
                "hp"                   : 720,
                "mp"                   : 252,
                "speed"                : 75,
                "vitality"             : 40,
                "wisdom"               : 50,
            },
            "stats_maxed"          : 8
        },
        //... (all other characters)
    ],
    "characters_hidden"    : false,
    "chars"                : 13,
    "created"              : "~1 year and 137 days ago",
    "desc1"                : "I fight for the glory of France.",
    "desc2"                : "https://www.youtube.com/nightfirecat/",
    "desc3"                : "https://JoanOfArcRotMG.wordpress.com/",
    "donator"              : true,
    "exp"                  : 13152470,
    "exp_rank"             : 513,
    "fame"                 : 8300,
    "fame_rank"            : 497,
    "guild"                : "Night Owls",
    "guild_rank"           : "Officer",
    "player"               : "JoanOfArc",
    "player_last_seen"     : "2013-08-02 07:04:16 at USNorthWest as Rogue",
    "rank"                 : 62,
    "skins"                : 0,
    "skins_rank"           : 103495
}
```

For `player=joanofarc&filter=player+chars+fame`:

```
{
    "chars"                : 13,
    "fame"                 : 8300
    "player"               : "JoanOfArc",
}
```

For `player=joanofarc&filter=-characters+desc1+desc2+desc3`:

```
{
    "account_fame"         : 35662,
    "account_fame_rank"    : 264,
    "characters_hidden"    : false,
    "chars"                : 13,
    "created"              : "~1 year and 137 days ago",
    "donator"              : true,
    "exp"                  : 13152470,
    "exp_rank"             : 513,
    "fame"                 : 8300,
    "fame_rank"            : 497,
    "guild"                : "Night Owls",
    "guild_rank"           : "Officer",
    "player"               : "JoanOfArc",
    "player_last_seen"     : "2013-08-02 07:04:16 at USNorthWest as Rogue",
    "rank"                 : 62,
    "skins"                : 0,
    "skins_rank"           : 103495
}
```
