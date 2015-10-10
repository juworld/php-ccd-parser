<?php namespace michalisantoniou6\PhpCCDParser;

class CCDParser
{
    protected $xml;
    protected $prescriptions = [];
    protected $problems = [];
    protected $labValues = [];
    protected $immunizations = [];
    protected $procedures = [];
    protected $vitalSigns = [];
    protected $allergies = [];
    protected $encounters = [];
    protected $carePlan = [];
    protected $demographics = [];
    protected $provider = [];

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
            $test = $xmlRoot->component[$i]->section->templateId->attributes()->root;

            // Medications
            if ($test == '2.16.840.1.113883.10.20.22.2.1.1') {
                $this->parsePrescriptions($xmlRoot->component[$i]->section);
            } // Allergies
            else if ($test == '2.16.840.1.113883.10.20.22.2.6.1') {
                $this->parseAllergies($xmlRoot->component[$i]->section);
            } // Encounters
            else if ($test == '2.16.840.1.113883.10.20.22.2.22' or
                $test == '2.16.840.1.113883.10.20.22.2.22.1'
            ) {
                $this->parseEncounters($xmlRoot->component[$i]->section);
            } // Immunizations
            else if ($test == '2.16.840.1.113883.10.20.22.2.2.1' or
                $test == '2.16.840.1.113883.10.20.22.2.2'
            ) {
                $this->parseImmunizations($xmlRoot->component[$i]->section);
            } // Labs
            else if ($test == '2.16.840.1.113883.10.20.22.2.3.1') {
                $this->parseLabValues($xmlRoot->component[$i]->section);
            } // Problems
            else if ($test == '2.16.840.1.113883.10.20.22.2.5.1' or
                $test == '2.16.840.1.113883.10.20.22.2.5'
            ) {
                $this->parseProblems($xmlRoot->component[$i]->section);
            } // Procedures
            else if ($test == '2.16.840.1.113883.10.20.22.2.7.1' or
                $test == '2.16.840.1.113883.10.20.22.2.7'
            ) {
                $this->parseProcedures($xmlRoot->component[$i]->section);
            }

            // Vitals
            if ($test == '2.16.840.1.113883.10.20.22.2.4.1') {
                $this->parseVitals($xmlRoot->component[$i]->section);
            }

            // Care Plan
            if ($test == '2.16.840.1.113883.10.20.22.2.10') {
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

            $this->prescriptions[$n]['date_range']['start'] = (string)$entry->substanceAdministration->effectiveTime->low['value'];
            $this->prescriptions[$n]['date_range']['end'] = (string)$entry->substanceAdministration->effectiveTime->high['value'];
            $this->prescriptions[$n]['product_name'] = (string)$entry->substanceAdministration->consumable->manufacturedProduct->manufacturedMaterial->code['displayName'];
            $this->prescriptions[$n]['product_code'] = (string)$entry->substanceAdministration->consumable->manufacturedProduct->manufacturedMaterial->code['code'];
            $this->prescriptions[$n]['product_code_system'] = (string)$entry->substanceAdministration->consumable->manufacturedProduct->manufacturedMaterial->code['codeSystem'];
            $this->prescriptions[$n]['translation']['name'] = (string)$entry->substanceAdministration->consumable->manufacturedProduct->manufacturedMaterial->code->translation['displayName'];
            $this->prescriptions[$n]['translation']['code_system'] = (string)$entry->substanceAdministration->consumable->manufacturedProduct->manufacturedMaterial->code->translation['codeSystemName'];
            $this->prescriptions[$n]['translation']['code'] = (string)$entry->substanceAdministration->consumable->manufacturedProduct->manufacturedMaterial->code->translation['code'];
            $this->prescriptions[$n]['dose_quantity']['value'] = (string)$entry->substanceAdministration->doseQuantity['value'];
            $this->prescriptions[$n]['dose_quantity']['unit'] = (string)$entry->substanceAdministration->doseQuantity['unit'];
            $this->prescriptions[$n]['rate_quantity']['value'] = (string)$entry->substanceAdministration->rateQuantity['value'];
            $this->prescriptions[$n]['rate_quantity']['unit'] = (string)$entry->substanceAdministration->rateQuantity['unit'];
            $this->prescriptions[$n]['precondition']['name'] = (string)$entry->substanceAdministration->precondition->criterion->value['displayName'];
            $this->prescriptions[$n]['precondition']['code'] = (string)$entry->substanceAdministration->precondition->criterion->value['code'];
            $this->prescriptions[$n]['precondition']['code_system'] = (string)$entry->substanceAdministration->precondition->criterion->value['codeSystem'];

            /*
             * Missing some fields here
             */
        }
    }

    private function parseAllergies($xmlAllergy)
    {
        foreach ($xmlAllergy->entry as $entry) {
            $n = count($this->allergies);

            $this->allergies[$n]['date_range']['start'] = (string)$entry->act->effectiveTime->low['value'];
            $this->allergies[$n]['date_range']['end'] = (string)$entry->act->effectiveTime->high['value'];
            $this->allergies[$n]['name'] = (string)$entry->act->entryRelationship->observation->code['displayName'];
            $this->allergies[$n]['code'] = (string)$entry->act->entryRelationship->observation->code['code'];
            $this->allergies[$n]['code_system'] = (string)$entry->act->entryRelationship->observation->code['codeSystem'];
            $this->allergies[$n]['code_system_name'] = (string)$entry->act->entryRelationship->observation->code['codeSystemName'];
            $this->allergies[$n]['allergen']['name'] = (string)$entry->act->entryRelationship->observation->participant->participantRole->playingEntity->code['displayName'];
            $this->allergies[$n]['allergen']['code'] = (string)$entry->act->entryRelationship->observation->participant->participantRole->playingEntity->code['code'];
            $this->allergies[$n]['allergen']['code_system'] = (string)$entry->act->entryRelationship->observation->participant->participantRole->playingEntity->code['codeSystem'];
            $this->allergies[$n]['allergen']['code_system_name'] = (string)$entry->act->entryRelationship->observation->participant->participantRole->playingEntity->code['codeSystemName'];
            $this->allergies[$n]['reaction_type']['name'] = (string)$entry->act->entryRelationship->observation->value['displayName'];
            $this->allergies[$n]['reaction_type']['code'] = (string)$entry->act->entryRelationship->observation->value['code'];
            $this->allergies[$n]['reaction_type']['code_system'] = (string)$entry->act->entryRelationship->observation->value['codeSystem'];
            $this->allergies[$n]['reaction_type']['code_system_name'] = (string)$entry->act->entryRelationship->observation->value['codeSystemName'];

            $entryRoot = $entry->act->entryRelationship->observation;
            foreach ($entryRoot->entryRelationship as $detail) {
                if (!is_object($detail->observation->templateId)) continue;
                $test = $detail->observation->templateId->attributes()->root;
                $varname = '';

                if ($test == '2.16.840.1.113883.10.20.22.4.9') $varname = 'reaction';
                if ($test == '2.16.840.1.113883.10.20.22.4.8') $varname = 'severity';

                if (!empty($varname)) {
                    $this->allergies[$n][$varname]['name'] = (string)$detail->observation->value['displayName'];
                    $this->allergies[$n][$varname]['code'] = (string)$detail->observation->value['code'];
                    $this->allergies[$n][$varname]['code_system'] = (string)$detail->observation->value['codeSystem'];
                    $this->allergies[$n][$varname]['code_system_name'] = (string)$detail->observation->value['codeSystemName'];
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
            $this->encounters[$n]['code_system'] = (string)$entry->encounter->code['codeSystem'];
            $this->encounters[$n]['code_system_name'] = (string)$entry->encounter->code['codeSystemName'];
            $this->encounters[$n]['code_system_version'] = (string)$entry->encounter->code['codeSystemVersion'];
            $this->encounters[$n]['finding']['name'] = (string)$entry->encounter->entryRelationship->observation->value['displayName'];
            $this->encounters[$n]['finding']['code'] = (string)$entry->encounter->entryRelationship->observation->value['code'];
            $this->encounters[$n]['finding']['code_system'] = (string)$entry->encounter->entryRelationship->observation->value['codeSystem'];
            $this->encounters[$n]['performer']['name'] = (string)$entry->encounter->performer->assignedEntity->code['displayName'];
            $this->encounters[$n]['performer']['code_system'] = (string)$entry->encounter->performer->assignedEntity->code['codeSystem'];
            $this->encounters[$n]['performer']['code'] = (string)$entry->encounter->performer->assignedEntity->code['code'];
            $this->encounters[$n]['performer']['code_system_name'] = (string)$entry->encounter->performer->assignedEntity->code['codeSystemName'];
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
            $this->immunizations[$n]['product']['code_system'] = (string)$entryRoot->consumable->manufacturedProduct->manufacturedMaterial->code['codeSystem'];
            $this->immunizations[$n]['product']['code_system_name'] = (string)$entryRoot->consumable->manufacturedProduct->manufacturedMaterial->code['codeSystemName'];
            $this->immunizations[$n]['product']['translation']['name'] = (string)$entryRoot->consumable->manufacturedProduct->manufacturedMaterial->code->translation['displayName'];
            $this->immunizations[$n]['product']['translation']['code'] = (string)$entryRoot->consumable->manufacturedProduct->manufacturedMaterial->code->translation['code'];
            $this->immunizations[$n]['product']['translation']['code_system'] = (string)$entryRoot->consumable->manufacturedProduct->manufacturedMaterial->code->translation['codeSystem'];
            $this->immunizations[$n]['product']['translation']['code_system_name'] = (string)$entryRoot->consumable->manufacturedProduct->manufacturedMaterial->code->translation['codeSystemName'];
            $this->immunizations[$n]['route']['name'] = (string)$entryRoot->routeCode['displayName'];
            $this->immunizations[$n]['route']['code'] = (string)$entryRoot->routeCode['code'];
            $this->immunizations[$n]['route']['code_system'] = (string)$entryRoot->routeCode['codeSystem'];
            $this->immunizations[$n]['route']['code_system_name'] = (string)$entryRoot->routeCode['codeSystemName'];
        }
    }

    private function parseLabValues($xmlLab)
    {
        foreach ($xmlLab->entry as $entry) {
            $n = count($this->labValues);

            $this->labValues[$n]['panel_name'] = (string)$entry->organizer->code['displayName'];
            $this->labValues[$n]['panel_code'] = (string)$entry->organizer->code['code'];
            $this->labValues[$n]['panel_code_system'] = (string)$entry->organizer->code['codeSystem'];
            $this->labValues[$n]['panel_code_system_name'] = (string)$entry->organizer->code['codeSystemName'];
            $this->labValues[$n]['results']['date'] = (string)$entry->organizer->component->observation->effectiveTime['value'];
            $this->labValues[$n]['results']['name'] = (string)$entry->organizer->component->observation->code['displayName'];
            $this->labValues[$n]['results']['code'] = (string)$entry->organizer->component->observation->code['code'];
            $this->labValues[$n]['results']['code_system'] = (string)$entry->organizer->component->observation->code['codeSystem'];
            $this->labValues[$n]['results']['code_system_name'] = (string)$entry->organizer->component->observation->code['codeSystemName'];
            $this->labValues[$n]['results']['value'] = (string)$entry->organizer->component->observation->value['value'];
            $this->labValues[$n]['results']['unit'] = (string)$entry->organizer->component->observation->value['unit'];
        }
    }

    private function parseProblems($xmlDx)
    {
        foreach ($xmlDx->entry as $entry) {
            $n = count($this->problems);
            $this->problems[$n]['date_range']['start'] = (string)$entry->act->effectiveTime->low['value'];
            $this->problems[$n]['date_range']['end'] = (string)$entry->act->effectiveTime->high['value'];
            $this->problems[$n]['name'] = (string)$entry->act->entryRelationship->observation->value['displayName'];
            $this->problems[$n]['code'] = (string)$entry->act->entryRelationship->observation->value['code'];
            $this->problems[$n]['code_system'] = (string)$entry->act->entryRelationship->observation->value['codeSystem'];
            $this->problems[$n]['translation']['name'] = (string)$entry->act->entryRelationship->observation->value->translation['displayName'];
            $this->problems[$n]['translation']['code'] = (string)$entry->act->entryRelationship->observation->value->translation['code'];
            $this->problems[$n]['translation']['code_system'] = (string)$entry->act->entryRelationship->observation->value->translation['codeSystem'];
            $this->problems[$n]['translation']['code_system_name'] = (string)$entry->act->entryRelationship->observation->value->translation['codeSystemName'];
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
            $this->procedures[$n]['code_system'] = (string)$entry->procedure->code['codeSystem'];
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

            // Pull each vital sign for a given date
            $this->vitalSigns[$n]['results'] = [];

            $m = 0;
            foreach ($entry->organizer->component as $component) {
                $this->vitalSigns[$n]['results'][$m]['name'] = (string)$component->observation->code['displayName'];
                $this->vitalSigns[$n]['results'][$m]['code'] = (string)$component->observation->code['code'];
                $this->vitalSigns[$n]['results'][$m]['code_system'] = (string)$component->observation->code['codeSystem'];
                $this->vitalSigns[$n]['results'][$m]['code_system_name'] = (string)$component->observation->code['codeSystemName'];
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
            $this->carePlan[$n]['code_system'] = (string)$entryRoot->code['codeSystem'];
            $this->carePlan[$n]['text'] = (string)$entryRoot->text;
            $this->carePlan[$n]['status'] = (string)$entryRoot->statusCode['code'];
        }
    }

    function getPatientCCD()
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

        return json_encode($patient, JSON_PRETTY_PRINT);
    }
}
