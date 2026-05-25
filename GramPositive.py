__version__="1.0"
import json
from string_comparator import lst_about
class GramPositive:
    def __init__(self,model:dict=None):
        self.model=model
    def tokenize(self,text):
        '''take text block, separate into sentences, remove punctuation, 
        and then separate into words'''
        text=text.lower()#make text lowercase

        text=text.replace("!",".").replace("?",".").replace(",","")#normalize punctuation for splitting and remove commas
        text=text.split(".")
        sentences=[]
        for sent in text:
            st=sent.split(" ")
            st = [word for word in st if word != ""]
            if st!=[]:
                sentences.append(st)

        return sentences

    def train_gram_model(self,input_list:dict,default_label="normal"):
        '''trains an n-gram model file by looking for common patterns in a dictionary of
        prelabled text strings
        input structure: {text:lebel,text:lebel,text:lebel} 
        model structure: model={"index":{0:0},"matrix":{"0:0":label,0:0":label}}'''
        if self.model:#load model from input
            word_index=self.model["index"]
            matrix_model=self.model["matrix"]
            default_label=self.model["default"]
            current_value=len(word_index)+1
        else:  #generate new model
            word_index={"*43$#00":0}
            matrix_model={}
            current_value=1#the last value in the index

        for text_block in input_list: #iterate though the whole training data and tokenize and process each example
            label=input_list[text_block]
            sentences=self.tokenize(text_block)#tokenize
            for sent in sentences:#iterate and process through each sentence
                prec="*43$#00" #set precedent to *43$#00 if there is no precedent (less chances for incidents)
                for word in sent: #take each word, put it in the model and find patterns
                    if word in word_index:#get the value from the index if already there 
                        value=word_index.get(word)
                    else:#add the word in index if not there and update the next value
                        word_index[word]=current_value
                        value=current_value
                        current_value+=1
                    pattern=f"{word_index[prec]}:{value}" #generate the pattern based on the precedent word
                    prec=word
                    if pattern not in matrix_model: #pair the lables to each pattern and add them to matrix
                        matrix_model[pattern]={label:1}# each label will get a score based on how often it is flagges in each text later allowing quantization
                    else:
                        labels=matrix_model[pattern]#update each label or add a new one for existent patterns
                        if label in labels:
                            labels[label]+=1
                        else:
                            labels[label]=1
                        matrix_model[pattern]=labels
        #package the model
        self.model={"default":default_label,
        "index":word_index,
        "matrix":matrix_model}
        return self.model
    def process_text(self,text,word_match=False):
        word_index=self.model["index"]
        matrix_model=self.model["matrix"]
        default_label=self.model["default"]
        scores={}
        sentences=self.tokenize(text)
        wordcount=0
        if word_match==True:
            all_words=[i for i in word_index]
        for sent in sentences:
            prec="*43$#00" #set precedent to *43$#00 if there is no precedent (less chances for incidents)
            for word in sent:
                if word not in word_index:
                    if word_match==True:
                        word=lst_about(word,all_words)
                    else:
                        pass
                if word in word_index:
                    wordcount+=1#count the words so that an average score can be calculated
                    value=word_index.get(word)
                    pattern=f"{word_index[prec]}:{value}"
                    prec=word #the prec only gets updated if the word exists, thus the function will comletely skip ant unknown words
                    if pattern in matrix_model: #get the patern scores but only if the pattern or word exists in the model
                        labels=matrix_model[pattern]
                        
                    else:#fallback for unknown patterns.
                        labels={default_label:1}
                    for label in labels: #update the scores with the current labels
                        if label in scores:
                            scores[label] += labels[label]
                        else:
                            scores[label] = labels[label]

        #calculate average to avoid getting higher scores for longer sentences
        if wordcount!=0:
            avg_scores={}
            for i in scores:
                avg_scores[i]=scores[i]/wordcount
            scores=avg_scores
        return scores
    def classify(self,text,word_match=False):
        results=self.process_text(text,word_match)
        verdict="none"
        highest=0
        for i in results:
            if results[i]>highest:
                verdict=i
        return verdict
    def quantize(self,level=90):
        '''trims the patterns that have more than level percent of all possible labels'''
        if self.model:
            matrix_model = self.model["matrix"]
            label_list=[]
            for pattern in matrix_model:#get all labels to determine the threshold for removal
                for i in matrix_model[pattern]:
                    if i not in label_list:
                        label_list.append(i)
            threshold=round((level*len(label_list))/100) #calculate treshold as [level] % of all labels
            quant_model={}
            for pattern in matrix_model:#remove all patterns over treshold
                if len(matrix_model[pattern])<=threshold:
                    quant_model[pattern]=matrix_model[pattern]
            self.model["matrix"]=quant_model
            return self.model
        else:
            print("please make sure there is a model to qunatize")
    def load_model(self,path="model.json"):
        with open(path,"r") as f:
            self.model=json.load(f)
    def save_model(self,path="model.json"):
        with open(path,"w") as f:
            json.dump(self.model,f)