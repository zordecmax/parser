##Web crawler PHP + Guzzle

Before run install package with command `composer install` 

Run script in console with argument `php index.php list.txt`.

In text file website link with separator | number which declare how many videos to be downloaded.
If script will not find enough video files on 1st page it will move to another page and take from there.
Video downloaded in order first Full HD then HD and if not find will download simple quality.
In file result.txt will be inserted all information about video: path to saved file| Title | Description | Category | Tags
