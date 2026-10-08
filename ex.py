import codecs
text = codecs.open('index.html', 'r', 'utf-8').read()
start = text.find('function saveAgency() {')
if start != -1:
    with codecs.open('scratch_save_func.txt', 'w', 'utf-8') as f:
        f.write(text[start:start+1800])
