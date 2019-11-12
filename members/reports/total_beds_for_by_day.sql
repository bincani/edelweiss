select
    date,
    sum(members),
    sum(juniors),
    sum(adult_guests),
    sum(child_guests),
    sum(members) + sum(juniors) + sum(adult_guests) + sum(child_guests) as total
from edelweiss_days where year(`date`) = 2019 group by date;
