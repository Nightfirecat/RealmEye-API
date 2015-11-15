the unofficial

#RealmEye API

A quick and easy portal to getting all the character information you need.

##How to use

Currently, the only method available is to get a player's personal and character info, as seen on RealmEye in JSON (or JSONP) format.

##Setup

To run this API on your server, you will need to ensure you have the proper
software installed, and correctly set up your environment.

To run the API, you will need the following installed on your server:

* Latest Apache (known working on 2.4.7)
* Latest PHP (known working on 5.6.0)
* *recommended:* Git (known working with 1.9.1)

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

### The `Realmeye-API-Version` header

In order to get the 'Realmeye-API-Version' header feature working properly,
further setup is necessary - namely, a
[smudge-clean filter](https://git-scm.com/book/en/v2/Customizing-Git-Git-Attributes#Keyword-Expansion)
must be set up on the environment. To do this, just follow the below steps:

1. Modify the directory's configuration (in `.git/config`) to add the following
  lines:
  
      [filter "expand_commit_id"]
          smudge = 'commit_smudge_filter.sh'
          clean = 'commit_clean_filter.sh'
  
2. Create the following files in any directory which is included in your `PATH`:
  
  a. `commit_smudge_filter.sh`
    
      #!/bin/bash
      
      # on checkout, replace '$Id$' with '$Id: <hash> $'
      commit_hash=`git log -1 --pretty=format:'%h'`
      sed -r -e "s/\\\$Id\\\$/\$Id: $commit_hash \$/g"
    
  b. `commit_clean_filter.sh`
    
      #!/bin/bash
      
      # on checkin, replace '$Id: <hash> $' with '$Id$'
      sed -r -e 's/\$Id: [a-fA-F0-9]+ \$/$Id$/g'
    
Once these steps are completed, the application should work as expected. Going
forward with updates, however, each file affected by the filter will need to
be checked out again after performing `git pull` for updates. A simple way
to ensure all files are updated with the filter (and are made up-to-date with
the repository) is to run the following after a `git pull`:

```
git checkout HEAD -- "$(git rev-parse --show-toplevel)"
```

This will perform `git checkout` for each file in the repository, which will
re-run the smudge filter, updating the commit hash.

P.S. If you are making use of the automated GitHub deployment, you will not
need to manually perform checkouts after every pull; it is included in the
deploy script.

* * *

##Requests

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
		<td>data_vars</td>
		<td>boolean</td>
		<td>optional</td>
		<td>If <code>true</code> is passed, all relevant <code>data-*</code> attributes will be returned (<a href="#response-values">see below for examples of such values</a>)</td>
	</tr>
	<tr>
		<td>filter</td>
		<td>string</td>
		<td>optional</td>
		<td>
			Accepts a space-separated string of variable names, with an optional leading hyphen.
			When not prefaced with a hyphen, directs the API only to return values with keys appearing in the given list.
			When prefaced with a hyphen, directs the API to return all usual values with keys not listed.
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
		<td>donator</td>
		<td>boolean (string)</td>
		<td>"true" if the player donated to Realmeye, "false" otherwise</td>
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
		<td>desc1, desc2, desc3</td>
		<td>string</td>
		<td>Full strings of each description line (by numbered line) of the player. If the given line is empty, returns ""</td>
	</tr>
	<tr>
		<td>characters</td>
		<td>array / string</td>
		<td>Array of displayed characters. If characters are hidden, returns "hidden"</td>
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
		<td>data_pet_id</td>
		<td>int</td>
		<td>Item <code>id</code> of the given pet. -1 if character has no pet (passed only if <code>data_vars</code> is <code>true</code>)</td>
	</tr>
	<tr>
		<td>pet</td>
		<td>string</td>
		<td>Pet type (not player-assigned pet name). "" if character has no pet</td>
	</tr>
	<tr>
		<td>character_dyes</td>
		<td>dict</td>
		<td>
			List of character dyes as strings.<br />
			<code>data_clothing_dye</code> is the numbered <code>id</code> of the <em>color</em>, not of the item. (for use in rendering character images, for example)<br />
			<code>clothing_dye</code> is the name of the large cloth/dye<br />
			<code>data_accessory_dye</code> is the numbered <code>id</code> of the <em>color</em>, not of the item. (for use in rendering charcter images, for example)<br />
			<code>accessory_dye</code> is the name of the small cloth/dye<br /><br />
			Data values are passed only if <code>data_vars</code> is <code>true</code>, and are 0 for undyed characters. Cloth/dye names are "" if un-dyed
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
		<td><code>id</code> assigned to that character's class (passed only if <code>data_vars</code> is <code>true</code>)</td>
	</tr>
	<tr>
		<td>data_skin_id</td>
		<td>int</td>
		<td><code>id</code> assigned to that character's skin. 0 if character is using the class's default skin (passed only if <code>data_vars</code> is <code>true</code>)</td>
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
			List of item <code>id</code>s as ints (if <code>data_vars</code> was <code>true</code>), and equipments as strings.<br />
			Data-variable keys are <code>data_weapon_id</code>, <code>data_ability_id</code>, <code>data_armor_id</code>, and <code>data_ring_id</code>. Name keys are <code>weapon</code>, <code>ability</code>, <code>armor</code>, and <code>ring</code>.<br />
			Empty slots' values are -1 and "Empty slot", respectively.
		</td>
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
		<td>stats</td>
		<td>dict</td>
		<td>
			List of individual base stats (hp, mp, attack, defense, speed, vitality, wisdom, and dexterity) as ints.
		</td>
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

	https://nightfirec.at/realmeye-api/?player=joanofarc
	https://nightfirec.at/realmeye-api/?id=PdT6pPU7qBN&callback=processPlayer
	https://nightfirec.at/realmeye-api/?player=joanofarc&filter=player+chars+fame
	https://nightfirec.at/realmeye-api/?player=joanofarc&filter=-characters+desc1+desc2+desc3
	https://nightfirec.at/realmeye-api/?player=joanofarc&pretty

###Sample responses

For `player=joanofarc&data_vars=true`:

```
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
	"desc1"                : "I fight for the glory of France.",
	"desc2"                : "https:\/\/www.youtube.com\/nightfirecat\/",
	"desc3"                : "https:\/\/JoanOfArcRotMG.wordpress.com\/",
	"characters"           : [
		{
			"data_pet_id"          : 32611,
			"pet"                  : "Gummy Bear",
			"character_dyes"       : {
				"data_clothing_dye"    : 150994946,
				"clothing_dye"         : "Large Blue Lace Cloth",
				"data_accessory_dye"   : 83886083,
				"accessory_dye"        : "Small Sweater Cloth"
			},
			"class"                : "Rogue",
			"level"                : 20,
			"cqc"                  : 4,
			"fame"                 : 608,
			"exp"                  : 805974,
			"place"                : 589,
			"equips"               : {
				"data_weapon_id"       : 3082,
				"weapon"               : "Dirk of Cronus",
				"data_ability_id"      : 2855,
				"ability"              : "Cloak of Ghostly Concealment",
				"data_armor_id"        : 3112,
				"armor"                : "Spectral Cloth Armor",
				"data_ring_id"         : 2978,
				"ring"                 : "Ring of the Pyramid"
			},
			"backpack"             : "true",
			"stats_maxed"          : 8,
			"stats"                : {
				"hp"                   : 720,
				"mp"                   : 252,
				"attack"               : 50,
				"defense"              : 25,
				"speed"                : 75,
				"vitality"             : 40,
				"wisdom"               : 50,
				"dexterity"            : 75
			},
			"last_seen"            : "2013-08-02 07:04:16",
			"last_server"          : "USNorthWest"
		},
		//... (all other characters)
	]
}
```

For `player=joanofarc&filter=player+chars+fame`:

```
{
	"player"               : "JoanOfArc",
	"chars"                : 13,
	"fame"                 : 8300
}
```

For `player=joanofarc&filter=-characters+desc1+desc2+desc3`:

```
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
	"last_seen"            : "2013-08-02 07:04:16 at USNorthWest as Rogue"
}
```

##Changelog

###v0.3.2; 9/16/2015
* Removed old code (and old changelog)
* Added .gitignore and .htaccess
* Closed #12
* README reflects domain change
