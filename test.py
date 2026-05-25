from GramPositive import GramPositive as gp

rec=gp()
rec.load_model("test_toxic _content_model.json")
print(rec.classify("This is a test sentence."))
print(rec.classify("I want to die."))