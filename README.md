Alligator Gar Migrations
========================

Simple command line tool to help with db/code migrations.

![Alligator Gar](http://www.marshbunny.com/stjohns/recipes/images/gar1.jpg)

Everything is about as basic as it gets.

 	php ./deploy.php /path/to/deploy/scripts/ 3
 	
 The tool will look for a hidden file called ".current_version" in the path specified to see the … current version. It will then either run all the "up" (if the current version > the version you'd like) or "down" (vice-versa) in the *.json files.
 
 	{
    	"up":[
        	"svn up file.php -r 3"
    	],
    	"down":[
    		"svn revert file.php -r 2"
    	]
	}
	
The above is an example of a *.json file. The json files must be numeric. ie. 1.json, 2.json, etc.

The tool will simply go through each version and run either the "up" or "down" commands.

It's named "Alligator Gar" Migrations because the teeth are sharp. The commands are executed via the php "exec()" function. So, there's nothing stopping "rm -rf /"