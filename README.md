Door Opener PHP Code
=======================

Some PHP code to handle data to and from an Arduino door opener.

Yes the code is horrible :) this was a quick solution to our missing garage door issue. But it works wondefully!

                                                               |---- LED (on when door open)
                                                               |
     [Web Server + MariaDB + PHP] ----{internet}---- [Arduino(pin)] ----- Magnet Switch on door
                                                               |
                                                               |
                                                               |---{ethernet}--- [Arduino(rfid)] --- RFID reader outside door

Notes:
 - make sure google.cache file is writable by PHP if you wish to use Android push notifications.
