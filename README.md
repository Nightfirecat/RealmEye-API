the unofficial

#RealmEye API

A quick and easy portal to getting all the character information you need.

##How to use

Currently, the only method available is to get a player's personal and character info, as seen on RealmEye in JSON (or JSONP) format.  
This can be done by querying

    http://webhost.ischool.uw.edu/~joatwood/realmeye_api/

To get your character information, this API requires a `player` or `id` parameter, with the player name stated after the `player` parameter, or the 11-character `id` of an unnamed player who appears on RealmEye [as it appears in their RealmEye URL](https://www.realmeye.com/recently-seen-unnamed-players).  
Additionally, there is an optional parameter, `callback`, used when requesting JSON with padding.

An example of two valid request URLs is shown below:

    http://webhost.ischool.uw.edu/~joatwood/realmeye_api/?player=joanofarc
    http://webhost.ischool.uw.edu/~joatwood/realmeye_api/?id=PdT6pPU7qBN&callback=processPlayer

If your request is processed successfully, you'll get a JSON response similar to what's below:  
(example is a snippet of the response to the first example URL)

{
    "player"               : "JoanOfArc",
    "chars"                : "13",
    "fame"                 : "8300",
    "fame_rank"            : "497",
    "exp"                  : "13152470",
    "exp_rank"             : "513",
    "rank"                 : "62",
    "account_fame"         : "35662",
    "account_fame_rank"    : "264",
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
            "level"                : "20",
            "cqc"                  : "4",
            "fame"                 : "608",
            "exp"                  : "805974",
            "place"                : "589",
            "equips"               : {
                "weapon"               : "Dirk of Cronus",
                "ability"              : "Cloak of Ghostly Concealment",
                "armor"                : "Spectral Cloth Armor",
                "ring"                 : "Ring of the Pyramid"
            },
            "backpack"             : "true",
            "stats_maxed"          : "8",
            "last_seen"            : "2013-08-02 07:04:16",
            "last_server"          : "USNorthWest"
        },
        //... (all other characters)
    ]
}

##Changelog

###v0.1; 08/03/2013

Initial instructions; source code not yet added. (needs refactoring/cleanup)