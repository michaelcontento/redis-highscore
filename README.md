Redis-Highscore
===============

You need a fast and reliable highscore for your game? Not afraid to use
Redis? This can be the perfect solution for you!

Pseudocode API
--------------

    //
    // Choose a namespace for the following instance of the
    // Highscore object. You can differ between multiple highscores 
    // with the namespace!
    //
    NAMESPACE = 'kills-per-player'
    NAMESPACE = 'building-per-player'

    //
    // Create the highscore object
    //
    HS = new Highscore(new Redis(), NAMESPACE)

    //
    // Now manipulate the highscore
    //
    HS.set('userA', 100)        # set the current score
    HS.increment('userB', 2)    # increment by 2
    HS.decrement('userC', 20)   # decrement by 20
    HS.remove('userA')          # removes userA
    HS.clear()                  # clears the whole highscore

    //
    // And finally you can read from the highscore
    // 
    HS.count()                  # Number of all entries 
    HS.countByScore(20, 50)     # Number of entries with score >= 20 and <= 50
    HS.rank('userA')            # Rank of userA
    HS.listByRank(100, 10)      # Infos for rank 10 till 110
    HS.listByScore(50, 20)      # Infos for the first 50 users with a score >= 20 


License
-------

    Copyright 2011 Michael Contento <michaelcontento@gmail.com>

    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at

        http://www.apache.org/licenses/LICENSE-2.0

    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    See the License for the specific language governing permissions and
    limitations under the License.
