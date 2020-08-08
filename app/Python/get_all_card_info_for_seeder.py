# coding: UTF-8
import json
import vardata
rarity4_cards_json = open(vardata.rarity4_cards_url, 'r')
rarity4_cards = json.load(rarity4_cards_json)
promotion_cards_json = open(vardata.promotion_cards_url, 'r')
promotion_cards = json.load(promotion_cards_json)
rarity3_cards_json = open(vardata.rarity3_cards_url, 'r')
rarity3_cards = json.load(rarity3_cards_json)
# print(check)
# print(str(output).decode("string-escape"))
# print(data)
# print(type(all_cards[0][1]["missionName"]))
'''
for models_name in rarity4_cards:
    for missions_name in rarity4_cards[models_name]:
        for ordinals in rarity4_cards[models_name][missions_name]:
            for card_infos in ordinals:
                print(card_infos['id'].encode('utf-8'))
                print(card_infos['name'].encode('utf-8'))
                print(missions_name.encode('utf-8'))
                print(models_name.encode('utf-8'))
'''
'''
for models_name in promotion_cards:
    for missions_name in promotion_cards[models_name]:
        for card_infos in promotion_cards[models_name][missions_name]:
            print(card_infos['id'].encode('utf-8'))
            print(card_infos['name'].encode('utf-8'))
            print(missions_name.encode('utf-8'))
            print(models_name.encode('utf-8'))
'''
for models_name in rarity3_cards:
    for missions_name in rarity3_cards[models_name]:
        for card_infos in rarity3_cards[models_name][missions_name]:
            print(card_infos['id'].encode('utf-8'))
            print(card_infos['name'].encode('utf-8'))
            print(missions_name.encode('utf-8'))
            print(models_name.encode('utf-8'))
'''
for mission_data in origin_data :
    for cards in mission_data['ordinal'] :
        for card in cards :
            print(card['id'] + ',' + card['name'])
'''
