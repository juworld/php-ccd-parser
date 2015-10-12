<?php namespace michalisantoniou6\PhpCCDParser;

class CCDParser
{
    protected $xml;
    public $allergies = [];
    public $carePlan = [];
    public $demographics = [];
    public $encounters = [];
    public $immunizations = [];
    public $labValues = [];
    public $prescriptions = [];
    public $problems = [];
    public $procedures = [];
    public $provider = [];
    public $vitalSigns = [];

    function __construct($xmlCCD = '')
    {
        if (!empty($xmlCCD)) {
            if (!is_object($xmlCCD)) {
                $xmlCCD = simplexml_load_string($xmlCCD);
            }
            $this->xml = $xmlCCD;
            $this->parse();
        }
    }

    private function parse()
    {
        $this->parseDemographics($this->xml->recordTarget->patientRole);

        $this->parseProvider($this->xml->recordTarget->patientRole);

        // Parse components
        $xmlRoot = $this->xml->component->structuredBody;
        $i = 0;
        while (is_object($xmlRoot->component[$i])) {
            $sectionCode = $xmlRoot->component[$i]->section->templateId->attributes()->root;

            // Medications
            if ($sectionCode == '2.16.840.1.113883.10.20.22.2.1.1') {
                $this->parsePrescriptions($xmlRoot->component[$i]->section);
            } // Allergies
            else if ($sectionCode == '2.16.840.1.113883.10.20.22.2.6.1') {
                $this->parseAllergies($xmlRoot->component[$i]->section);
            } // Encounters
            else if ($sectionCode == '2.16.840.1.113883.10.20.22.2.22'
                or $sectionCode == '2.16.840.1.113883.10.20.22.2.22.1'
            ) {
                $this->parseEncounters($xmlRoot->component[$i]->section);
            } // Immunizations
            else if ($sectionCode == '2.16.840.1.113883.10.20.22.2.2.1'
                or $sectionCode == '2.16.840.1.113883.10.20.22.2.2'
            ) {
                $this->parseImmunizations($xmlRoot->component[$i]->section);
            } // Labs
            else if ($sectionCode == '2.16.840.1.113883.10.20.22.2.3.1') {
                $this->parseLabValues($xmlRoot->component[$i]->section);
            } // Problems
            else if ($sectionCode == '2.16.840.1.113883.10.20.22.2.5.1'
                or $sectionCode == '2.16.840.1.113883.10.20.22.2.5'
            ) {
                $this->parseProblems($xmlRoot->component[$i]->section);
            } // Procedures
            else if ($sectionCode == '2.16.840.1.113883.10.20.22.2.7.1'
                or $sectionCode == '2.16.840.1.113883.10.20.22.2.7'
            ) {
                $this->parseProcedures($xmlRoot->component[$i]->section);
            }

            // Vitals
            if ($sectionCode == '2.16.840.1.113883.10.20.22.2.4.1') {
                $this->parseVitals($xmlRoot->component[$i]->section);
            }

            // Care Plan
            if ($sectionCode == '2.16.840.1.113883.10.20.22.2.10') {
                $this->parseCareplan($xmlRoot->component[$i]->section);
            }

            $i++;
        }
    }

    private function parseDemographics($xmlDemo)
    {
        $this->demographics['addr']['street'] = [
            (string)$xmlDemo->addr->streetAddressLine[0],
            (string)$xmlDemo->addr->streetAddressLine[1]
        ];

        $this->demographics['addr']['city'] = (string)$xmlDemo->addr->city;
        $this->demographics['addr']['state'] = (string)$xmlDemo->addr->state;
        $this->demographics['addr']['postalCode'] = (string)$xmlDemo->addr->postalCode;
        $this->demographics['addr']['country'] = (string)$xmlDemo->addr->country;
        $this->demographics['phone']['number'] = (string)$xmlDemo->telecom['value'];
        $this->demographics['phone']['use'] = (string)$xmlDemo->telecom['use'];
        $this->demographics['name']['first'] = (string)$xmlDemo->patient->name->given;
        $this->demographics['name']['last'] = (string)$xmlDemo->patient->name->family;
        $this->demographics['gender'] = (string)$xmlDemo->patient->administrativeGenderCode['code'];
        $this->demographics['birthdate'] = (string)$xmlDemo->patient->birthTime['value'];
        $this->demographics['maritalStatus'] = (string)$xmlDemo->patient->maritalStatusCode['displayName'];
        $this->demographics['race'] = (string)$xmlDemo->patient->raceCode['displayName'];
        $this->demographics['ethnicity'] = (string)$xmlDemo->patient->ethnicGroupCode['displayName'];
        $this->demographics['language'] = (string)$xmlDemo->patient->languageCommunication->languageCode['code'];
    }

    private function parseProvider($xmlDemo)
    {
        $this->provider['organization']['name'] = (string)$xmlDemo->providerOrganization->name;
        $this->provider['organization']['phone'] = (string)$xmlDemo->providerOrganization->telecom['value'];
        $this->provider['organization']['addr']['street'] = [
            (string)$xmlDemo->providerOrganization->addr->streetAddressLine[0],
            (string)$xmlDemo->providerOrganization->addr->streetAddressLine[1]
        ];
        $this->provider['organization']['addr']['city'] = (string)$xmlDemo->providerOrganization->addr->city;
        $this->provider['organization']['addr']['state'] = (string)$xmlDemo->providerOrganization->addr->state;
        $this->provider['organization']['addr']['postalCode'] = (string)$xmlDemo->providerOrganization->addr->postalCode;
        $this->provider['organization']['addr']['country'] = (string)$xmlDemo->providerOrganization->addr->country;
    }

    private function parsePrescriptions($xmlMed)
    {
        foreach ($xmlMed->entry as $entry) {
            $n = count($this->prescriptions);

            $this->prescriptions[$n]['dateRange']['start'] = (string)$entry->substanceAdministration->effectiveTime->low['value'];
            $this->prescriptions[$n]['dateRange']['end'] = (string)$entry->substanceAdministration->effectiveTime->high['value'];
            $this->prescriptions[$n]['productName'] = (string)$entry->substanceAdministration->consumable->manufacturedProduct->manufacturedMaterial->code['displayName'];
            $this->prescriptions[$n]['productCode'] = (string)$entry->substanceAdministration->consumable->manufacturedProduct->manufacturedMaterial->code['code'];
            $this->prescriptions[$n]['productCodeSystem'] = (string)$entry->substanceAdministration->consumable->manufacturedProduct->manufacturedMaterial->code['codeSystem'];
            $this->prescriptions[$n]['translation']['name'] = (string)$entry->substanceAdministration->consumable->manufacturedProduct->manufacturedMaterial->code->translation['displayName'];
            $this->prescriptions[$n]['translation']['codeSystem'] = (string)$entry->substanceAdministration->consumable->manufacturedProduct->manufacturedMaterial->code->translation['codeSystemName'];
            $this->prescriptions[$n]['translation']['code'] = (string)$entry->substanceAdministration->consumable->manufacturedProduct->manufacturedMaterial->code->translation['code'];
            $this->prescriptions[$n]['doseQuantity']['value'] = (string)$entry->substanceAdministration->doseQuantity['value'];
            $this->prescriptions[$n]['doseQuantity']['unit'] = (string)$entry->substanceAdministration->doseQuantity['unit'];
            $this->prescriptions[$n]['rateQuantity']['value'] = (string)$entry->substanceAdministration->rateQuantity['value'];
            $this->prescriptions[$n]['rateQuantity']['unit'] = (string)$entry->substanceAdministration->rateQuantity['unit'];
            $this->prescriptions[$n]['precondition']['name'] = (string)$entry->substanceAdministration->precondition->criterion->value['displayName'];
            $this->prescriptions[$n]['precondition']['code'] = (string)$entry->substanceAdministration->precondition->criterion->value['code'];
            $this->prescriptions[$n]['precondition']['codeSystem'] = (string)$entry->substanceAdministration->precondition->criterion->value['codeSystem'];

            /*
             * Missing some fields here
             */
        }
    }

    private function parseAllergies($xmlAllergy)
    {
        foreach ($xmlAllergy->entry as $entry) {
            $n = count($this->allergies);

            $this->allergies[$n]['dateRange']['start'] = (string)$entry->act->effectiveTime->low['value'];
            $this->allergies[$n]['dateRange']['end'] = (string)$entry->act->effectiveTime->high['value'];
            $this->allergies[$n]['name'] = (string)$entry->act->entryRelationship->observation->code['displayName'];
            $this->allergies[$n]['code'] = (string)$entry->act->entryRelationship->observation->code['code'];
            $this->allergies[$n]['codeSystem'] = (string)$entry->act->entryRelationship->observation->code['codeSystem'];
            $this->allergies[$n]['codeSystemName'] = (string)$entry->act->entryRelationship->observation->code['codeSystemName'];
            $this->allergies[$n]['allergen']['name'] = (string)$entry->act->entryRelationship->observation->participant->participantRole->playingEntity->code['displayName'];
            $this->allergies[$n]['allergen']['code'] = (string)$entry->act->entryRelationship->observation->participant->participantRole->playingEntity->code['code'];
            $this->allergies[$n]['allergen']['codeSystem'] = (string)$entry->act->entryRelationship->observation->participant->participantRole->playingEntity->code['codeSystem'];
            $this->allergies[$n]['allergen']['codeSystemName'] = (string)$entry->act->entryRelationship->observation->participant->participantRole->playingEntity->code['codeSystemName'];
            $this->allergies[$n]['reactionType']['name'] = (string)$entry->act->entryRelationship->observation->value['displayName'];
            $this->allergies[$n]['reactionType']['code'] = (string)$entry->act->entryRelationship->observation->value['code'];
            $this->allergies[$n]['reactionType']['codeSystem'] = (string)$entry->act->entryRelationship->observation->value['codeSystem'];
            $this->allergies[$n]['reactionType']['codeSystemName'] = (string)$entry->act->entryRelationship->observation->value['codeSystemName'];

            $entryRoot = $entry->act->entryRelationship->observation;
            foreach ($entryRoot->entryRelationship as $detail) {
                if (!is_object($detail->observation->templateId)) continue;

                $sectionCode = $detail->observation->templateId->attributes()->root;
                $sectionName = '';

                if ($sectionCode == '2.16.840.1.113883.10.20.22.4.9') $sectionName = 'reaction';
                if ($sectionCode == '2.16.840.1.113883.10.20.22.4.8') $sectionName = 'severity';

                if (!empty($sectionName)) {
                    $this->allergies[$n][$sectionName]['name'] = (string)$detail->observation->value['displayName'];
                    $this->allergies[$n][$sectionName]['code'] = (string)$detail->observation->value['code'];
                    $this->allergies[$n][$sectionName]['codeSystem'] = (string)$detail->observation->value['codeSystem'];
                    $this->allergies[$n][$sectionName]['codeSystemName'] = (string)$detail->observation->value['codeSystemName'];
                }
            }
        }
    }

    private function parseEncounters($xmlEnc)
    {
        foreach ($xmlEnc->entry as $entry) {
            $n = count($this->encounters);

            $this->encounters[$n]['date'] = (string)$entry->encounter->effectiveTime['value'];
            $this->encounters[$n]['name'] = (string)$entry->encounter->code['displayName'];
            $this->encounters[$n]['code'] = (string)$entry->encounter->code['code'];
            $this->encounters[$n]['codeSystem'] = (string)$entry->encounter->code['codeSystem'];
            $this->encounters[$n]['codeSystemName'] = (string)$entry->encounter->code['codeSystemName'];
            $this->encounters[$n]['codeSystemVersion'] = (string)$entry->encounter->code['codeSystemVersion'];
            $this->encounters[$n]['finding']['name'] = (string)$entry->encounter->entryRelationship->observation->value['displayName'];
            $this->encounters[$n]['finding']['code'] = (string)$entry->encounter->entryRelationship->observation->value['code'];
            $this->encounters[$n]['finding']['codeSystem'] = (string)$entry->encounter->entryRelationship->observation->value['codeSystem'];
            $this->encounters[$n]['performer']['name'] = (string)$entry->encounter->performer->assignedEntity->code['displayName'];
            $this->encounters[$n]['performer']['codeSystem'] = (string)$entry->encounter->performer->assignedEntity->code['codeSystem'];
            $this->encounters[$n]['performer']['code'] = (string)$entry->encounter->performer->assignedEntity->code['code'];
            $this->encounters[$n]['performer']['codeSystemName'] = (string)$entry->encounter->performer->assignedEntity->code['codeSystemName'];
            $this->encounters[$n]['location']['organization'] = (string)$entry->encounter->participant->participantRole->code['displayName'];
            $this->encounters[$n]['location']['street'] = [
                (string)$entry->encounter->participant->participantRole->addr->streetAddressLine[0],
                (string)$entry->encounter->participant->participantRole->addr->streetAddressLine[1]
            ];
            $this->encounters[$n]['location']['city'] = (string)$entry->encounter->participant->participantRole->addr->city;
            $this->encounters[$n]['location']['state'] = (string)$entry->encounter->participant->participantRole->addr->state;
            $this->encounters[$n]['location']['zip'] = (string)$entry->encounter->participant->participantRole->addr->postalCode;
            $this->encounters[$n]['location']['country'] = (string)$entry->encounter->participant->participantRole->addr->country;
        }
    }

    private function parseImmunizations($xmlImm)
    {
        foreach ($xmlImm->entry as $entry) {
            $n = count($this->immunizations);
            $entryRoot = $entry->substanceAdministration;
            $this->immunizations[$n]['date'] = (string)$entryRoot->effectiveTime['value'];
            $this->immunizations[$n]['product']['name'] = (string)$entryRoot->consumable->manufacturedProduct->manufacturedMaterial->code['displayName'];
            $this->immunizations[$n]['product']['code'] = (string)$entryRoot->consumable->manufacturedProduct->manufacturedMaterial->code['code'];
            $this->immunizations[$n]['product']['codeSystem'] = (string)$entryRoot->consumable->manufacturedProduct->manufacturedMaterial->code['codeSystem'];
            $this->immunizations[$n]['product']['codeSystemName'] = (string)$entryRoot->consumable->manufacturedProduct->manufacturedMaterial->code['codeSystemName'];
            $this->immunizations[$n]['product']['translation']['name'] = (string)$entryRoot->consumable->manufacturedProduct->manufacturedMaterial->code->translation['displayName'];
            $this->immunizations[$n]['product']['translation']['code'] = (string)$entryRoot->consumable->manufacturedProduct->manufacturedMaterial->code->translation['code'];
            $this->immunizations[$n]['product']['translation']['codeSystem'] = (string)$entryRoot->consumable->manufacturedProduct->manufacturedMaterial->code->translation['codeSystem'];
            $this->immunizations[$n]['product']['translation']['codeSystemName'] = (string)$entryRoot->consumable->manufacturedProduct->manufacturedMaterial->code->translation['codeSystemName'];
            $this->immunizations[$n]['route']['name'] = (string)$entryRoot->routeCode['displayName'];
            $this->immunizations[$n]['route']['code'] = (string)$entryRoot->routeCode['code'];
            $this->immunizations[$n]['route']['codeSystem'] = (string)$entryRoot->routeCode['codeSystem'];
            $this->immunizations[$n]['route']['codeSystemName'] = (string)$entryRoot->routeCode['codeSystemName'];
        }
    }

    private function parseLabValues($xmlLab)
    {
        foreach ($xmlLab->entry as $entry) {
            $n = count($this->labValues);

            $this->labValues[$n]['panelName'] = (string)$entry->organizer->code['displayName'];
            $this->labValues[$n]['panelCode'] = (string)$entry->organizer->code['code'];
            $this->labValues[$n]['panelCodeSystem'] = (string)$entry->organizer->code['codeSystem'];
            $this->labValues[$n]['panelCodeSystemName'] = (string)$entry->organizer->code['codeSystemName'];
            $this->labValues[$n]['results']['date'] = (string)$entry->organizer->component->observation->effectiveTime['value'];
            $this->labValues[$n]['results']['name'] = (string)$entry->organizer->component->observation->code['displayName'];
            $this->labValues[$n]['results']['code'] = (string)$entry->organizer->component->observation->code['code'];
            $this->labValues[$n]['results']['codeSystem'] = (string)$entry->organizer->component->observation->code['codeSystem'];
            $this->labValues[$n]['results']['codeSystemName'] = (string)$entry->organizer->component->observation->code['codeSystemName'];
            $this->labValues[$n]['results']['value'] = (string)$entry->organizer->component->observation->value['value'];
            $this->labValues[$n]['results']['unit'] = (string)$entry->organizer->component->observation->value['unit'];
        }
    }

    private function parseProblems($xmlDx)
    {
        foreach ($xmlDx->entry as $entry) {
            $n = count($this->problems);
            $this->problems[$n]['dateRange']['start'] = (string)$entry->act->effectiveTime->low['value'];
            $this->problems[$n]['dateRange']['end'] = (string)$entry->act->effectiveTime->high['value'];
            $this->problems[$n]['name'] = (string)$entry->act->entryRelationship->observation->value['displayName'];
            $this->problems[$n]['code'] = (string)$entry->act->entryRelationship->observation->value['code'];
            $this->problems[$n]['codeSystem'] = (string)$entry->act->entryRelationship->observation->value['codeSystem'];
            $this->problems[$n]['translation']['name'] = (string)$entry->act->entryRelationship->observation->value->translation['displayName'];
            $this->problems[$n]['translation']['code'] = (string)$entry->act->entryRelationship->observation->value->translation['code'];
            $this->problems[$n]['translation']['codeSystem'] = (string)$entry->act->entryRelationship->observation->value->translation['codeSystem'];
            $this->problems[$n]['translation']['codeSystemName'] = (string)$entry->act->entryRelationship->observation->value->translation['codeSystemName'];
            $this->problems[$n]['status'] = (string)$entry->act->entryRelationship->observation->entryRelationship->observation->value['displayName'];
        }
    }

    private function parseProcedures($xmlProc)
    {
        foreach ($xmlProc->entry as $entry) {
            $n = count($this->procedures);

            $this->procedures[$n]['date'] = (string)$entry->procedure->effectiveTime['value'];
            $this->procedures[$n]['name'] = (string)$entry->procedure->code['displayName'];
            $this->procedures[$n]['code'] = (string)$entry->procedure->code['code'];
            $this->procedures[$n]['codeSystem'] = (string)$entry->procedure->code['codeSystem'];
            $this->procedures[$n]['performer']['organization'] = (string)$entry->procedure->performer->assignedEntity->addr->name;
            $this->procedures[$n]['performer']['street'] = [
                (string)$entry->procedure->performer->assignedEntity->addr->streetAddressLine[0],
                (string)$entry->procedure->performer->assignedEntity->addr->streetAddressLine[1]
            ];
            $this->procedures[$n]['performer']['city'] = (string)$entry->procedure->performer->assignedEntity->addr->city;
            $this->procedures[$n]['performer']['state'] = (string)$entry->procedure->performer->assignedEntity->addr->state;
            $this->procedures[$n]['performer']['zip'] = (string)$entry->procedure->performer->assignedEntity->addr->postalCode;
            $this->procedures[$n]['performer']['country'] = (string)$entry->procedure->performer->assignedEntity->addr->country;

            if (is_object($entry->procedure->performer->assignedEntity->telecom)) {
                $this->procedures[$n]['performer']['phone'] = (string)$entry->procedure->performer->assignedEntity->telecom['value'];
            }
        }
    }

    private function parseVitals($xmlVitals)
    {
        foreach ($xmlVitals->entry as $entry) {
            $n = count($this->vitalSigns);
            $this->vitalSigns[$n]['date'] = (string)$entry->organizer->effectiveTime['value'];

            $this->vitalSigns[$n]['results'] = [];

            $m = 0;
            foreach ($entry->organizer->component as $component) {
                $this->vitalSigns[$n]['results'][$m]['name'] = (string)$component->observation->code['displayName'];
                $this->vitalSigns[$n]['results'][$m]['code'] = (string)$component->observation->code['code'];
                $this->vitalSigns[$n]['results'][$m]['codeSystem'] = (string)$component->observation->code['codeSystem'];
                $this->vitalSigns[$n]['results'][$m]['codeSystemName'] = (string)$component->observation->code['codeSystemName'];
                $this->vitalSigns[$n]['results'][$m]['value'] = (string)$component->observation->value['value'];
                $this->vitalSigns[$n]['results'][$m]['unit'] = (string)$component->observation->value['unit'];
                $m++;
            }
        }
    }

    private function parseCareplan($xmlCare)
    {
        foreach ($xmlCare->entry as $entry) {
            $n = count($this->carePlan);

            if (is_object($entry->act->code)) $entryRoot = $entry->act;
            elseif (is_object($entry->observation->code)) $entryRoot = $entry->observation;
            else continue;

            $this->carePlan[$n]['name'] = (string)$entryRoot->code['displayName'];
            $this->carePlan[$n]['code'] = (string)$entryRoot->code['code'];
            $this->carePlan[$n]['codeSystem'] = (string)$entryRoot->code['codeSystem'];
            $this->carePlan[$n]['text'] = (string)$entryRoot->text;
            $this->carePlan[$n]['status'] = (string)$entryRoot->statusCode['code'];
        }
    }

    public function getParsedCCD($format = 'json')
    {
        $patient = [];
        $patient['demographics'] = $this->demographics;
        $patient['provider'] = $this->provider;
        $patient['prescriptions'] = $this->prescriptions;
        $patient['problems'] = $this->problems;
        $patient['labValues'] = $this->labValues;
        $patient['immunizations'] = $this->immunizations;
        $patient['procedures'] = $this->procedures;
        $patient['vitalSigns'] = $this->vitalSigns;
        $patient['allergies'] = $this->allergies;
        $patient['encounters'] = $this->encounters;
        $patient['carePlan'] = $this->carePlan;

        if($format == 'json') return json_encode($patient, JSON_PRETTY_PRINT);
        if($format == 'object') return json_decode(json_encode($patient));
        if($format == 'array') return (array) $patient;
    }
}
