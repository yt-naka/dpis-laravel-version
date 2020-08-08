import requests
from bs4 import BeautifulSoup

missions = [
    {'name': '第', 'url': 133000, 'ordinal': 8},  # url 13300n
    {'name': 'GM', 'url': 133100, 'ordinal': 10},  # 13310n
    {'name': 'JM', 'url': 133200, 'ordinal': 8},  # 13320n
    {'name': 'GDM', 'url': 133300, 'ordinal': 10}  # 13330n
]

ccc = [
    {'name': 'SH', 'ordinal': 8},
    {'name': 'UM', 'ordinal': 12},
    {'name': 'BM', 'ordinal': 2}
]

aaa = requests.get("http://dbh-hikaku.net/?mode=cate&cbid=2277659&csid=0")
aaa_soup = BeautifulSoup(aaa.content, "html.parser")
aaa_a = aaa_soup.select("div.nav > ul.nl > li > a")
aaa_a.reverse()
i = 0
links = []
output = []
for aaa_b in aaa_a:
    if(i >= 42 and i <= 64 and i != 50):  # 何弾か
        # print(str(i) + ' ' + aaa_b.get_text() + ' ' + aaa_b.get('href'))
        links.append(aaa_b.get('href'))
    i = i + 1

j = 0
for mission in missions:
    output.append({'mission_name': mission['name']})
    output[j]['ordinal'] = []
    for i in range(mission['ordinal']):
        print(mission['name'] + str(i+1) + '弾')
        output[j]['ordinal'].append([])
        dbh = {'origin_data': requests.get(
            "http://carddass.com/dbh/cardlist/?search=true&category="
            + str(mission['url'] + i+1))}
        dbh['soup'] = BeautifulSoup(dbh['origin_data'].content, "html.parser")
        dbh['products'] = dbh['soup'].select("div.card")
        for product in dbh['products']:
            product_rare = product.select("div.rare")
            # ★★★★
            rarity4 = '\u2605\u2605\u2605\u2605'.decode('unicode-escape')
            if product_rare[0].get_text(strip=True) == rarity4:
                product_id_name = product.select("ul.prof li")
                product_id = product_id_name[0].get_text(strip=True)
                product_name = product_id_name[1].get_text(strip=True)
                # print( 'id ' + product_id )
                # print( 'name ' + product_name )
                output.append(product_name)
                print(output)
                output[j]['ordinal'][i].append(
                    {'id': product_id, 'name': product_name.encode('utf-8')})
        # print( '\n' )
    j = j+1
# print(check)
# print(str(output).decode("string-escape"))
# print(data)
# print(type(all_cards[0][1]["missionName"]))

'''
for mission_data in origin_data :
    for cards in mission_data['ordinal'] :
        for card in cards :
            print(card['id'] + ',' + card['name'])
'''
